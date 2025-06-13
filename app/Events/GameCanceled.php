<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Models\Game;
use App\Models\Player;
use App\States\GameState;
use Thunk\Verbs\Event;

class GameCanceled extends Event
{
    use HasGame;

    public int $admin_id;

    public function validate()
    {
        $this->assert(
            $this->state(GameState::class)->status === 'upcoming',
            'Game is not upcoming',
        );
    }

    public function applyToGame(GameState $game)
    {
        $game->status = 'canceled';
    }

    public function handle()
    {
        $game = Game::find($this->game_id);
        $game->status = 'canceled';
        $game->save();

        $game->players->each(function (Player $player) {
            $player->user->update([
                'current_game_id' => null,
                'current_player_id' => null,
            ]);
            $player->delete();
        });
    }
}
