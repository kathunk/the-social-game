<?php

namespace App\Events;

use App\Events\Traits\HasGameTemplate;
use App\Models\GameTemplate;
use App\States\GameTemplateState;
use Thunk\Verbs\Event;

class GameTemplateUnarchived extends Event
{
    use HasGameTemplate;

    public function apply(GameTemplateState $game_template)
    {
        $game_template->is_archived = false;
    }

    public function handle()
    {
        GameTemplate::withoutGlobalScope('not_archived')
            ->find($this->game_template_id)
            ->update([
                'is_archived' => false,
            ]);
    }
}
