<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\GameState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

class GameEnded extends Event
{
    #[StateId(GameState::class)]
    public int $game_id;

    public function validate()
    {
        $this->assert(
            $this->state(GameState::class)->status === 'active',
            'Game is not active',
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
