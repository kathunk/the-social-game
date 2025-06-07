<?php

namespace App\Events\Traits;

use App\Models\GameMode;
use App\States\GameModeState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

trait HasGameMode
{
    #[StateId(GameModeState::class)]
    public int $game_mode_id;

    public function gameMode()
    {
        return GameMode::find($this->game_mode_id);
    }
}
