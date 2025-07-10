<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\GameState;
use App\Events\Traits\HasGame;
use App\Models\ModifierConfiguration;
use App\States\ModifierConfigurationState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

class ModifierConfigurationDeleted extends Event
{
    use HasGame;

    #[StateId(ModifierConfigurationState::class)]
    public int $modifier_configuration_id;

    public function applyToGame(GameState $state)
    {
        $state->modifier_configuration_ids = $state->modifier_configuration_ids->filter(fn ($id) => $id !== $this->modifier_configuration_id);
    }

    public function handle()
    {
        ModifierConfiguration::find($this->modifier_configuration_id)->delete();
    }
}
