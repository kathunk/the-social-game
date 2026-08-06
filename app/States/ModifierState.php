<?php

namespace App\States;

use App\Modifiers\BaseModifierClass;
use App\Modifiers\ModifierRegistry;
use Thunk\Verbs\State;

class ModifierState extends State
{
    public int $game_id;

    public string $class_key;

    public array $modifier_data;

    public function handler(): BaseModifierClass
    {
        return ModifierRegistry::retrieveFromState($this->class_key, $this);
    }

    public function game(): GameState
    {
        return GameState::load($this->game_id);
    }
}
