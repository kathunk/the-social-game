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
use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Model;

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

    public static function fromTemplate(
        GameTemplate $template,
        Carbon $starts_at,
        User $user,
        ?array $challenges = null,
        bool $is_public,
        bool $requires_admin_approval_to_join
    ): self
    {
        $game_id = GameCreated::fire(
            user_id: $user->id,
            name: $template->name,
            game_template_id: $template->id,
            type: $template->type,
            min_players: $template->min_players,
            max_players: $template->max_players,
            is_public: $template->is_public,
            requires_admin_approval_to_join: $template->requires_admin_approval_to_join,
            team_names: $template->team_names,
            challenges: $template->challenges,
            starts_at: $starts_at,
        )->game_id;

        foreach ($template->team_names as $team_name) {
            TeamCreated::fire(
                game_id: $game_id,
                name: $team_name,
            );
        }

        $challenges = $challenges ?? $template->challenges;

        $next_challenge_starts_at = $starts_at->copy();

        $challenges_with_times = collect($challenges)->map(function ($duration, $class_key) use ($next_challenge_starts_at) {
            return [
                'starts_at' => $next_challenge_starts_at,
                'ends_at' => $next_challenge_starts_at->copy()->addMinutes($duration),
                'class_key' => $class_key,
            ];
        });

        foreach ($challenges_with_times as $challenge) {
            ChallengeCreated::fire(
                game_id: $game_id,
                starts_at: $challenge['starts_at'],
                ends_at: $challenge['ends_at'],
                class_key: $challenge['class_key'],
            );
        }

        Verbs::commit();

        return self::find($game_id);
    }

    public function start()
    {
        GameStarted::fire(game_id: $this->id);

        $challenge = $this->challenges->sortBy('starts_at')->first();

        ChallengeStarted::fire(challenge_id: $challenge->id, game_id: $this->id);

        return $this->fresh();
    }

    public function end()
    {
        GameEnded::fire(game_id: $this->id);
    }
}
