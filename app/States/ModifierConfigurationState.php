<?php

namespace App\States;

use Thunk\Verbs\State;

class ModifierConfigurationState extends State
{
    public int $game_id;

    public string $modifier_key;

    public array $modifier_data = [];

    public function game(): GameState
    {
        return GameState::load($this->game_id);
    }
}
