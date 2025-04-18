<?php

namespace App\Events;

use App\Models\Game;
use Thunk\Verbs\Event;
use App\States\GameState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

class GameStarted extends Event
{
    #[StateId(GameState::class)]
    public int $game_id;

    public function validate()
    {
        $this->assert(
            $this->state(GameState::class)->status === 'upcoming',
            'Game is not upcoming'
        );
    }

    public function applyToGame(GameState $state)
    {
        $state->status = 'active';
    }

    public function handle()
    {
        Game::find($this->game_id)->update(['status' => 'active']);
    }
}
