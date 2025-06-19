<?php

namespace App\Models;

use App\Events\ChallengeCreated;
use App\Events\ChallengeStarted;
use App\Events\GameCanceled;
use App\Events\GameCreated;
use App\Events\GameEnded;
use App\Events\GameStarted;
use App\Events\ModifierCreated;
use App\Events\TeamCreated;
use App\States\GameState;
use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Thunk\Verbs\Facades\Verbs;

class Game extends Model
{
    use HasSnowflakes;

    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_public' => 'boolean',
        'requires_admin_approval_to_join' => 'boolean',
        'social_links' => 'array',
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

    public function modifiers()
    {
        return $this->hasMany(Modifier::class);
    }

    public function currentChallenge()
    {
        return $this->belongsTo(Challenge::class, 'current_challenge_id');
    }

    public function gameTemplate()
    {
        return $this->belongsTo(GameTemplate::class);
    }

    public function gameMode()
    {
        return $this->belongsTo(GameMode::class);
    }

    public static function fromTemplate(
        GameTemplate $template,
        GameMode $game_mode,
        Carbon $starts_at,
        User $user,
        bool $requires_admin_approval_to_join,
        ?bool $is_public = false,
        ?array $challenges = null,
        ?int $challenge_length_override = null,
        ?array $social_links = null,
    ): self {
        $game_id = GameCreated::fire(
            user_id: $user->id,
            name: $game_mode->name,
            game_template_id: $template->id,
            game_mode_id: $game_mode->id,
            type: $game_mode->type,
            min_players: $game_mode->min_players,
            max_players: $game_mode->max_players,
            is_public: $is_public,
            requires_admin_approval_to_join: $requires_admin_approval_to_join,
            team_names: $template->team_names,
            challenges: $template->challenges,
            starts_at: $starts_at,
            ends_at: $starts_at->copy()->addMinutes($template->totalDuration),
            code: self::uniqueGameCode(),
            challenge_length_override: $challenge_length_override,
            social_links: $social_links,
        )->game_id;

        Verbs::commit();

        $game = self::find($game_id);

        $user->promoteToGameAdmin($game, $user);

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

        if ($this->challenge_length_override) {
            $challenges = array_map(function ($challenge) {
                $challenge['duration'] = $this->challenge_length_override;

                return $challenge;
            }, $challenges);
        }

        $used_challenge_keys = [];
        $mapped_challenges = [];

        foreach ($challenges as $challenge) {
            $available = array_values(array_diff($challenge['challenge_keys'], $used_challenge_keys));

            // If there's at least one key that hasn't been used, pick one at random
            if (! empty($available)) {
                $chosen_key = $available[array_rand($available)];
            } else {
                // Fall back to a random key from the original list
                $chosen_key = $challenge['challenge_keys'][array_rand($challenge['challenge_keys'])];
            }

            $used_challenge_keys[] = $chosen_key;

            $mapped_challenges[] = [
                'challenge_keys' => [$chosen_key],
                'duration' => $challenge['duration'],
            ];
        }

        $next_challenge_starts_at = $this->starts_at->copy();

        $challenges_with_times = collect($mapped_challenges)->reduce(function ($carry, $challenge) use ($next_challenge_starts_at) {
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

        foreach ($this->gameTemplate->modifiers as $modifier) {
            ModifierCreated::fire(
                game_id: $this->id,
                class_key: $modifier,
            );
        }

        Verbs::commit();

        GameStarted::fire(game_id: $this->id);

        Verbs::commit();

        $challenge = $this->challenges->sortBy('starts_at')->first();

        ChallengeStarted::fire(challenge_id: $challenge->id, game_id: $this->id);

        Verbs::commit();

        return $this->fresh();
    }

    public function getIsJoinableAttribute(): bool
    {
        if ($this->status === 'ended') {
            return false;
        }

        if (
            ! is_null($this->gameTemplate->max_players) &&
            $this->players->count() >= $this->gameTemplate->max_players
        ) {
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

    public function cancel(User $admin)
    {
        GameCanceled::fire(
            game_id: $this->id,
            admin_id: $admin->id,
        );
    }

    public function getTotalDurationAttribute(): int
    {
        $challenges = collect($this->gameTemplate->challenges);

        if ($this->challenge_length_override) {
            return $this->challenge_length_override * $challenges->count();
        }

        return $challenges->sum(fn ($c) => $c['duration'] ?? 0);
    }
}
