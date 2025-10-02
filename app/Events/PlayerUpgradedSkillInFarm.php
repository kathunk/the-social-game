<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\ModifierState;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasGame;

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
    }

    public function handle()
    {
        $this->modifier()->updateModelWithStateData();
    }
}
