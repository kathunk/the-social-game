<?php

namespace App\Events\Traits;

use App\States\GameState;

trait HasActiveGame
{
    use HasGame;

    public function validateActiveGame()
    {
        $this->assert(
            $this->state(GameState::class)->status === 'active',
            'Game is not active'
        );
    }
}
