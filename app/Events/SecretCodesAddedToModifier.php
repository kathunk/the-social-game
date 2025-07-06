<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;

class SecretCodesAddedToModifier extends Event
{
    use HasGame, HasModifier;

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

    public function applyToModifier(ModifierState $modifier)
    {
        $modifier->modifier_data['unused_codes'] = $this->codes;
    }

    public function handle()
    {
        // You think you're calling shots, you got the wrong number. I love Benjamin Franklin more than his own mother. - Lil Wayne
    }
}
