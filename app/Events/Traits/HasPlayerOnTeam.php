<?php

namespace App\Events\Traits;

use App\States\PlayerState;
use App\States\TeamState;

trait HasPlayerOnTeam
{
    use HasActivePlayer, HasTeam;

    public function validatePlayer()
    {
        $this->assert(
            $this->state(TeamState::class)->player_ids->contains($this->player_id),
            'Player is not on the team'
        );

        $this->assert(
            $this->state(PlayerState::class)->team_id === $this->team_id,
            'Player is not on the team'
        );
    }
}
