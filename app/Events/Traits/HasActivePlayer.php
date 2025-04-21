<?php

namespace App\Events\Traits;

use App\States\GameState;
use App\States\PlayerState;

trait HasActivePlayer
{
    use HasPlayer;

    public function validateActivePlayer()
    {
        $this->assert(
            $this->state(PlayerState::class)->status === 'active',
            'Player is not active'
        );

        $this->assert(
            $this->state(GameState::class)->resigned_player_ids->doesntContain($this->player_id),
            'Player has already resigned',
        );
    }
}
