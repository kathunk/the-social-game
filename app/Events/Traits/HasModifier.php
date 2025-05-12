<?php

namespace App\Events\Traits;

use App\Models\Modifier;
use App\States\GameState;
use App\States\ModifierState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

trait HasModifier
{
    #[StateId(ModifierState::class)]
    public int $modifier_id;

    public function validateModifier()
    {
        $this->assert(
            $this->state(GameState::class)->modifier_ids->contains($this->modifier_id),
            'Modifier is not in game'
        );
    }

    public function modifier()
    {
        return Modifier::find($this->modifier_id);
    }
}
