<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\GameState;
use App\States\ModifierState;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasActivePlayer;
use App\Modifiers\Classes\FarmActions;
use App\Modifiers\Classes\FarmSkills;

class PlayerUpgradedSkillInFarm extends Event
{
    use HasActivePlayer, HasModifier, HasGame;

    public string $skill_name;

    public int $xp_cost;

    public function validate()
    {
        // @todo validate player is at requisite level
        // @todo validate player has enough XP
        // @todo validate that the modifier is a FarmSkills modifier]
    }

    public function apply(ModifierState $modifier)
    {
        $modifier->modifier_data = collect($modifier->modifier_data)
            ->map(function ($data, $player_id) {
                if ($player_id !== $this->player_id) {
                    return $data;
                }

                $data["skills"][$this->skill_name] =
                    $data["skills"][$this->skill_name] + 1;
                $data["xp"] = $data["xp"] - $this->xp_cost;

                return $data;
            })
            ->toArray();

        if ($this->skill_name === 'Porter') {
            $game_state = $this->state(GameState::class);
            $actions_state = $game_state->modifiers()->firstWhere('class_key', FarmActions::key());
            $actions_state->modifier_data = collect($actions_state->modifier_data)
                ->map(function ($data, $player_id) {
                    if ($player_id !== $this->player_id) {
                        return $data;
                    }

                    $data['grain_capacity'] = $data['grain_capacity'] + 5;

                    return $data;
                })->toArray();
        }
    }

    public function handle()
    {
        $this->game()->modifiers->each(fn ($modifier) => $modifier->updateModelWithStateData());
    }
}
