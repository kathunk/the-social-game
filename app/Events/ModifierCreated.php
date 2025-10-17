<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Models\Game;
use App\Models\Modifier;
use App\States\GameState;
use App\States\ModifierState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class ModifierCreated extends Event
{
    use HasGame;

    #[StateId(ModifierState::class)]
    public ?int $modifier_id = null;

    public string $class_key;

    public ?array $modifier_data = null;

    public function applyToGame(GameState $game)
    {
        $game->modifier_ids->push($this->modifier_id);
    }

    public function applyToModifier(ModifierState $modifier)
    {
        $modifier->game_id = $this->game_id;
        $modifier->class_key = $this->class_key;

        if ($this->modifier_data) {
            $modifier->modifier_data = $this->modifier_data;

            return;
        }

        // this is only in here for backwards compatibility with old events

        $config = $this->state(GameState::class)->modifierConfigurations()->firstWhere('modifier_key', $this->class_key);

        if ($config) {
            $modifier->modifier_data = $config->modifier_data;
        } else {
            $modifier->modifier_data = $modifier->handler()->dataArrayForState(Game::find($this->game_id));
        }
    }

    public function handle()
    {
        Modifier::create([
            'id' => $this->modifier_id,
            'game_id' => $this->game_id,
            'class_key' => $this->class_key,
            'modifier_data' => $this->state(ModifierState::class)->modifier_data,
        ]);
    }
}
