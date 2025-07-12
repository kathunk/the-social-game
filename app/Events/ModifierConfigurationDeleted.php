<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifierConfiguration;
use App\States\GameState;
use Thunk\Verbs\Event;

class ModifierConfigurationDeleted extends Event
{
    use HasGame, HasModifierConfiguration;

    public function applyToGame(GameState $state)
    {
        $state->modifier_configuration_ids = $state->modifier_configuration_ids->filter(fn ($id) => $id !== $this->modifier_configuration_id);
    }

    public function handle()
    {
        $this->modifierConfiguration()->delete();
    }
}
