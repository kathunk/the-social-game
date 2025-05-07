<?php

namespace App\Events\Traits;

use App\Models\Team;
use App\States\GameState;
use App\States\TeamState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

trait HasTeam
{
    #[StateId(TeamState::class)]
    public int $team_id;

    public function validateTeam()
    {
        $this->assert(
            $this->state(GameState::class)->team_ids->contains($this->team_id),
            'Team is not in the game'
        );

        $this->assert(
            $this->state(TeamState::class)->game_id === $this->game_id,
            'Team is not in the game'
        );
    }

    public function team()
    {
        return Team::find($this->team_id);
    }
}
