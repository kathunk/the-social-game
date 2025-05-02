<?php

namespace App\Events\Traits;

use App\States\GameTemplateState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

trait HasGameTemplate
{
    #[StateId(GameTemplateState::class)]
    public int $game_template_id;
}
