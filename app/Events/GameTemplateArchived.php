<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\Models\GameTemplate;
use App\States\GameTemplateState;
use App\Events\Traits\HasGameTemplate;

class GameTemplateArchived extends Event
{
    use HasGameTemplate;

    public function apply(GameTemplateState $game_template)
    {
        $game_template->is_archived = true;
    }

    public function handle()
    {
        GameTemplate::find($this->game_template_id)->update([
            'is_archived' => true,
        ]);
    }
}
