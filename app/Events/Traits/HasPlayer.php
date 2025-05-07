<?php

namespace App\Events\Traits;

use App\Models\Player;
use App\States\GameState;
use App\States\PlayerState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

trait HasPlayer
{
    #[StateId(PlayerState::class)]
    public int $player_id;

    public function validatePlayer()
    {
        $this->assert(
            $this->state(GameState::class)->player_ids->contains($this->player_id),
            'Player is not in the game'
        );

        $this->assert(
            $this->state(PlayerState::class)->game_id === $this->game_id,
            'Player is not in the game'
        );
    }

    public function player()
    {
        return Player::find($this->player_id);
    }
}
