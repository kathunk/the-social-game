<?php

namespace App\Events;

use App\Events\Traits\HasActiveGame;
use App\Events\Traits\HasChallenge;
use App\Models\Challenge;
use App\Models\Game;
use App\Notifications\ChallengeStartedNotification;
use App\States\ChallengeState;
use App\States\GameState;
use Illuminate\Support\Facades\Log;
use Thunk\Verbs\Event;
use Thunk\Verbs\Facades\Verbs;

class ChallengeStarted extends Event
{
    use HasActiveGame, HasChallenge;

    public array $challenge_data;

    public function validate()
    {
        $this->assert(
            $this->state(GameState::class)->challenges()->filter(fn ($c) => $c->status === 'active')->count() === 0,
            'Cannot have more than one active challenge'
        );
    }

    public function applyToChallenge(ChallengeState $challenge)
    {
        $challenge->status = 'active';
        $challenge->challenge_data = $this->challenge_data;
    }

    public function applyToGame(GameState $state)
    {
        $state->current_challenge_id = $this->challenge_id;

        $state->modifiers()->each(function ($modifier) {
            $modifier->handler()->onChallengeStarted(
                game_state: $this->state(GameState::class),
                challenge_state: $this->state(ChallengeState::class),
                modifier_state: $modifier,
            );
        });
    }

    public function handle(ChallengeState $state)
    {
        $game = Game::find($this->game_id);
        $game->update(['current_challenge_id' => $this->challenge_id]);

        $challenge = Challenge::find($this->challenge_id);
        $challenge->update([
            'status' => 'active',
            'challenge_data' => $state->challenge_data,
        ]);

        if ($this->challenge()->round_number === 1) {
            return;
        }

        Verbs::unlessReplaying(function () use ($game, $challenge) {
            $this->game()->players()->each(function ($player) use ($game, $challenge) {
                // Skip players who have resigned
                if ($player->status === 'resigned') {
                    Log::debug('Skipping notification for resigned player', [
                        'user_id' => $player->user->id,
                        'player_id' => $player->id,
                        'challenge_id' => $challenge->id,
                    ]);
                    return;
                }

                $user = $player->user;

                // Check if user wants notifications for this specific event
                if ($user->wantsNotificationFor('notify_on_challenge_start')) {
                    Log::info('Sending challenge started notification', [
                        'user_id' => $user->id,
                        'challenge_id' => $challenge->id,
                        'game_id' => $game->id,
                        'game_name' => $game->name,
                    ]);

                    $user->notify(new ChallengeStartedNotification($challenge, $game));
                } else {
                    Log::debug('User does not want notifications for challenge_started', [
                        'user_id' => $user->id,
                        'challenge_id' => $challenge->id,
                        'preferences' => $user->notification_preferences,
                    ]);
                }
            });
        });
    }
}
