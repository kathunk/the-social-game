<?php

namespace App\Models;

use App\Events\GameEnded;
use App\States\GameState;
use App\Events\GameCreated;
use App\Events\GameStarted;
use App\Events\TeamCreated;
use Illuminate\Support\Carbon;
use Thunk\Verbs\Facades\Verbs;
use App\Events\ChallengeCreated;
use App\Events\ChallengeStarted;
use App\Events\UserAdmittedToGame;
use Glhd\Bits\Database\HasSnowflakes;
use App\Events\UserRequestedToJoinGame;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Game extends Model
{
    use HasSnowflakes;

    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function state(): GameState
    {
        return GameState::load($this->id);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function admins()
    {
        return $this->belongsToMany(User::class, 'game_admins');
    }

    public function applications()
    {
        return $this->hasMany(GameApplication::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function challenges()
    {
        return $this->hasMany(Challenge::class);
    }

    public function currentChallenge()
    {
        return $this->belongsTo(Challenge::class, 'current_challenge_id');
    }

    public function gameTemplate()
    {
        return $this->belongsTo(GameTemplate::class);
    }

    public static function fromTemplate(
        GameTemplate $template,
        Carbon $starts_at,
        User $user,
        bool $is_public,
        bool $requires_admin_approval_to_join,
        ?array $challenges = null,
    ): self
    {
        $game_id = GameCreated::fire(
            user_id: $user->id,
            name: $template->name,
            game_template_id: $template->id,
            type: $template->type,
            min_players: $template->min_players,
            max_players: $template->max_players,
            is_public: $is_public,
            requires_admin_approval_to_join: $requires_admin_approval_to_join,
            team_names: $template->team_names,
            challenges: $template->challenges,
            starts_at: $starts_at,
            ends_at: $starts_at->copy()->addMinutes($template->totalDuration),
            code: self::uniqueGameCode(),
        )->game_id;

        Verbs::commit();

        $game = self::find($game_id);

        $user->promoteToGameAdmin($game);

        $user->requestToJoinGame($game);

        if ($game->requires_admin_approval_to_join) {
            $user->admitToGame($game, $user);
        }

        return self::find($game_id);
    }

    public static function uniqueGameCode()
    {
        $code = random_int(1000, 9999);

        while (self::where('code', $code)->exists()) {
            $code = random_int(1000, 9999);
        }

        return $code;
    }

    public function start()
    {
        foreach ($this->gameTemplate->team_names as $team_name) {
            TeamCreated::fire(
                game_id: $this->id,
                name: $team_name,
            );
        }

        $challenges = $this->gameTemplate->challenges;

        $next_challenge_starts_at = $this->starts_at->copy();

        $challenges_with_times = collect($challenges)->reduce(function ($carry, $challenge) use ($next_challenge_starts_at) {
            if (empty($carry)) {
                $starts_at = $next_challenge_starts_at;
            } else {
                $last_challenge = end($carry);
                $starts_at = $last_challenge['ends_at'];
            }
            
            $ends_at = $starts_at->copy()->addMinutes($challenge['duration']);
            
            $carry[] = [
                'starts_at' => $starts_at,
                'ends_at' => $ends_at,
                'class_key' => collect($challenge['challenge_keys'])->random(),
            ];
            
            return $carry;
        }, []);

        foreach ($challenges_with_times as $challenge) {
            ChallengeCreated::fire(
                game_id: $this->id,
                starts_at: $challenge['starts_at'],
                ends_at: $challenge['ends_at'],
                class_key: $challenge['class_key'],
            );
        }

        GameStarted::fire(game_id: $this->id);

        $challenge = $this->challenges->sortBy('starts_at')->first();

        ChallengeStarted::fire(challenge_id: $challenge->id, game_id: $this->id);

        return $this->fresh();
    }

    public function getIsJoinableAttribute(): bool
    {
        if ($this->status === 'complete') {
            return false;
        }

        if ($this->players->count() >= $this->gameTemplate->max_players) {
            return false;
        }

        if ($this->players_can_join_late) {
            return true;
        }

        if ($this->status === 'upcoming') {
            return true;
        }

        return false;
    }

    public function getUrlAttribute(): string
    {
        return route('pre-game-lobby', $this->id);
    }

    public function end()
    {
        GameEnded::fire(game_id: $this->id);
    }
}
