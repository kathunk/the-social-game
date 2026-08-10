<?php

namespace App\Events;

use App\Events\Traits\HasActiveGame;
use App\Models\Game;
use App\Notifications\GameEndedNotification;
use App\States\GameState;
use Illuminate\Support\Facades\Log;
use Thunk\Verbs\Event;
use Thunk\Verbs\Facades\Verbs;

class GameEnded extends Event
{
    use HasActiveGame;

    public function applyToGame(GameState $game)
    {
        $game->status = 'ended';
        $game->current_challenge_id = null;
    }

    public function handle()
    {
        $game = Game::find($this->game_id);
        $game->status = 'ended';
        $game->current_challenge_id = null;
        $game->save();

        Verbs::unlessReplaying(function () use ($game) {
            $this->game()->players()->each(function ($player) use ($game) {
                if ($player->status === 'resigned') {
                    return;
                }

                $user = $player->user;

                if ($user->wantsNotificationFor('notify_on_game_end')) {
                    // A broken notification stack must never block the game
                    try {
                        $user->notify(new GameEndedNotification($game));
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            });
        });
    }
}
