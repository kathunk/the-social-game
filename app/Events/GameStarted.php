<?php

namespace App\Events;

use App\Models\Game;
use App\Notifications\GameStartedNotification;
use Illuminate\Support\Facades\Log;
use Thunk\Verbs\Event;
use App\States\GameState;
use App\Events\Traits\HasGame;
use Thunk\Verbs\Facades\Verbs;

class GameStarted extends Event
{
    use HasGame;

    public function validate()
    {
        $game = $this->state(GameState::class);

        $this->assert(
            $game->status === 'upcoming',
            'Game is not upcoming'
        );

        $this->assert(
            $game->player_ids->count() >= $game->template()->min_players,
            'Game does not have enough players'
        );
    }

    public function applyToGame(GameState $state)
    {
        $state->status = 'active';
        $state->players_can_join_late = $state->template()->players_can_join_late;

        $state->modifiers()->each(function ($modifier) {
            $modifier->handler()->onGameStarted(
                game_state: $this->state(GameState::class),
                modifier_state: $modifier,
            );
        });
    }

    public function handle()
    {
        $game = Game::find($this->game_id);
        $game->update(['status' => 'active']);

        $game->modifiers->each(function ($modifier) {
            $modifier->updateModelWithStateData();
        });

        Verbs::unlessReplaying(function () use ($game) {
            $this->game()->players->each(function ($player) use ($game) {
                $user = $player->user;
                
                // Check if user wants notifications for this specific event
                if ($user->wantsNotificationFor('notify_on_game_start')) {
                    Log::info('Sending game started notification', [
                        'user_id' => $user->id,
                        'game_id' => $game->id,
                        'game_name' => $game->name,
                    ]);
                    
                    $user->notify(new GameStartedNotification($game));
                } else {
                    Log::debug('User does not want notifications for game_started', [
                        'user_id' => $user->id,
                        'game_id' => $game->id,
                        'preferences' => $user->notification_preferences,
                    ]);
                }
            });
        });
    }
}
