<?php

namespace App\Events\Traits;

use App\States\GameState;
use App\Models\ModifierConfiguration;
use App\States\ModifierConfigurationState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

trait HasModifierConfiguration
{
    #[StateId(ModifierConfigurationState::class)]
    public int $modifier_configuration_id;

    public function validateModifier()
    {
        $this->assert(
            $this->state(GameState::class)->modifier_configuration_ids->contains($this->modifier_configuration_id),
            'Modifier configuration is not in game'
        );
    }

    public function modifierConfiguration()
    {
        return ModifierConfiguration::find($this->modifier_configuration_id);
    }
}
