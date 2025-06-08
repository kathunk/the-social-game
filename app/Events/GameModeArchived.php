<?php

namespace App\Events;

use App\Events\Traits\HasGameMode;
use App\Models\GameMode;
use App\States\GameModeState;
use Thunk\Verbs\Event;

class GameModeArchived extends Event
{
    use HasGameMode;

    public function apply(GameModeState $game_mode)
    {
        $game_mode->is_archived = true;
    }

    public function handle()
    {
        GameMode::find($this->game_mode_id)->update([
            'is_archived' => true,
        ]);
    }
}
