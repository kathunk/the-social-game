<?php

namespace App\Events;

use App\Events\Traits\HasActiveGame;
use App\Models\Game;
use App\States\GameState;
use Thunk\Verbs\Event;

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
    }
}
