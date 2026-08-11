<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Models\Game;
use App\Notifications\GameStartedNotification;
use App\States\GameState;
use Illuminate\Support\Facades\Log;
use Thunk\Verbs\Event;
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
            if (! $game->gameMode->has_notifications) {
                return;
            }

            $game->players->each(function ($player) use ($game) {
                if ($player->wantsGameNotifications()) {
                    $player->user->notify(new GameStartedNotification($game, $player));
                }
            });
        });
    }
}
