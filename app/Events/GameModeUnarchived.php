<?php

namespace App\Events;

use App\Events\Traits\HasGameMode;
use App\Models\GameMode;
use App\States\GameModeState;
use Thunk\Verbs\Event;

class GameModeUnarchived extends Event
{
    use HasGameMode;

    public function apply(GameModeState $game_mode)
    {
        $game_mode->is_archived = false;
    }

    public function handle()
    {
        GameMode::withoutGlobalScope('not_archived')
            ->find($this->game_mode_id)
            ->update([
                'is_archived' => false,
            ]);
    }
}
