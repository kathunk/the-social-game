<?php

namespace App\Events;

use App\Events\Traits\HasGameTemplate;
use App\Models\GameTemplate;
use App\States\GameTemplateState;
use Thunk\Verbs\Event;

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
