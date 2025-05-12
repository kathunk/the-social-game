<?php

namespace App\Events\Traits;

use App\Models\GameTemplate;
use App\States\GameTemplateState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

trait HasGameTemplate
{
    #[StateId(GameTemplateState::class)]
    public int $game_template_id;

    public function gameTemplate()
    {
        return GameTemplate::find($this->game_template_id);
    }
}
