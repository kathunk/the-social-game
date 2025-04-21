<?php

namespace App\Events;

use App\Events\Traits\HasActiveGame;
use App\Models\Game;
use App\States\GameState;
use Thunk\Verbs\Event;

class GameEnded extends Event
{
    use HasActiveGame;

    public function validate()
    {
        $this->assert(
            $this->state(GameState::class)->current_challenge_id === null,
            'Game has a current challenge',
        );
    }

    public function applyToGame(GameState $game)
    {
        $game->status = 'ended';
    }

    public function handle()
    {
        $game = Game::find($this->game_id);
        $game->status = 'ended';
        $game->save();
    }
}
