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
                // Skip players who have resigned
                if ($player->status === 'resigned') {
                    Log::debug('Skipping notification for resigned player', [
                        'user_id' => $player->user->id,
                        'player_id' => $player->id,
                        'game_id' => $game->id,
                    ]);

                    return;
                }

                $user = $player->user;

                // Check if user wants notifications for this specific event
                if ($user->wantsNotificationFor('notify_on_game_end')) {
                    Log::info('Sending game ended notification', [
                        'user_id' => $user->id,
                        'game_id' => $game->id,
                        'game_name' => $game->name,
                    ]);

                    $user->notify(new GameEndedNotification($game));
                } else {
                    Log::debug('User does not want notifications for game_ended', [
                        'user_id' => $user->id,
                        'game_id' => $game->id,
                        'preferences' => $user->notification_preferences,
                    ]);
                }
            });
        });
    }
}
