<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifierConfiguration;
use App\States\ModifierConfigurationState;

class SecretCodesAddedToModifier extends Event
{
    use HasGame, HasModifierConfiguration;

    public array $codes;

    public function validate()
    {
        $this->assert(
            count(array_unique($this->codes)) === count($this->codes),
            'Each code must be unique'
        );

        $this->assert(
            collect($this->codes)->every(fn($code) => strlen($code) <= 100),
            'Codes cannot be more than 100 characters'
        );
    }

    public function apply(ModifierConfigurationState $state)
    {
        $state->modifier_data['unused_codes'] = $this->codes;
    }

    public function handle()
    {
        $config = $this->modifierConfiguration();
        $config->modifier_data = $this->state(ModifierConfigurationState::class)->modifier_data;
        $config->save();
    }
}
