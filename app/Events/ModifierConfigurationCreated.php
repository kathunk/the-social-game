<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\GameState;
use App\Events\Traits\HasGame;
use App\Models\ModifierConfiguration;
use App\States\ModifierConfigurationState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

class ModifierConfigurationCreated extends Event
{
    use HasGame;

    #[StateId(ModifierConfigurationState::class)]
    public ?int $modifier_configuration_id = null;

    public string $modifier_key;

    public array $modifier_data = [];

    public function applyToModifierConfiguration(ModifierConfigurationState $state)
    {
        $state->modifier_key = $this->modifier_key;
        $state->modifier_data = $this->modifier_data;
        $state->game_id = $this->game_id;
    }

    public function applyToGame(GameState $state)
    {
        $state->modifier_configuration_ids->push($this->modifier_configuration_id);
    }

    public function handle()
    {
        ModifierConfiguration::create([
            'id' => $this->modifier_configuration_id,
            'game_id' => $this->game_id,
            'modifier_key' => $this->modifier_key,
            'modifier_data' => $this->modifier_data,
        ]);
    }
}
