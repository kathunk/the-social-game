<?php

namespace App\Events\Traits;

use App\Models\Game;
use App\States\GameState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

trait HasGame
{
    #[StateId(GameState::class)]
    public int $game_id;

    public function game()
    {
        return Game::find($this->game_id);
    }
}
