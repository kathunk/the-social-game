<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\GameState;
use App\Events\Traits\HasGame;
use App\Models\ModifierConfiguration;

class ModifierConfigurationDeleted extends Event
{
    use HasGame, HasModifierConfiguration;

    public function applyToGame(GameState $state)
    {
        $state->modifier_configuration_ids = $state->modifier_configuration_ids->filter(fn ($id) => $id !== $this->modifier_configuration_id);
    }

    public function handle()
    {
        ModifierConfiguration::find($this->modifier_configuration_id)->delete();
    }
}
