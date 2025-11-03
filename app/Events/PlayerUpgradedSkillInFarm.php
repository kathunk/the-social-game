<?php

namespace App\Events;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Modifiers\Classes\FarmActions;
use App\Modifiers\Classes\FarmSkills;
use App\States\GameState;
use App\States\ModifierState;
use Thunk\Verbs\Event;

class PlayerUpgradedSkillInFarm extends Event
{
    use HasActivePlayer, HasGame, HasModifier;

    public string $skill_name;

    public int $xp_cost;

    public function validate()
    {
        $game = $this->state(GameState::class);
        $skills_state = $game->modifiers()->firstWhere('class_key', FarmSkills::key());
        $player_data = $skills_state->modifier_data[$this->player_id];

        // Validate skill name is valid
        $valid_skill_names = collect(FarmSkills::SKILLS)->pluck('name')->toArray();
        $this->assert(
            in_array($this->skill_name, $valid_skill_names),
            'Invalid skill name: '.$this->skill_name,
        );

        // Validate player has enough XP to upgrade skill
        $player_xp = $player_data['xp'];
        $this->assert(
            $player_xp >= $this->xp_cost,
            'Player does not have enough XP (has '.$player_xp.', needs '.$this->xp_cost.')',
        );

        // Validate player is at requisite level (can only upgrade by 1 level at a time)
        $current_skill_level = $player_data['skills'][$this->skill_name];
        $expected_cost = match ($current_skill_level) {
            0 => 1,
            1 => 3,
            2 => 5,
        };

        $this->assert(
            $this->xp_cost === $expected_cost,
            'XP cost does not match expected cost for skill level (expected '.$expected_cost.', got '.$this->xp_cost.')',
        );

        $this->assert(
            $current_skill_level < 3,
            'Skill is already at maximum level',
        );
    }

    public function apply(ModifierState $modifier)
    {
        // this is just to handle old events :/
        if ($this->skill_name === 'Porter' || $this->skill_name === 'Tactician' || $this->skill_name === 'Strategist') {
            return;
        }

        $modifier->modifier_data = collect($modifier->modifier_data)
            ->map(function ($data, $player_id) {
                if ($player_id !== $this->player_id) {
                    return $data;
                }

                $data['skills'][$this->skill_name] =
                    $data['skills'][$this->skill_name] + 1;
                $data['xp'] = $data['xp'] - $this->xp_cost;

                return $data;
            })
            ->toArray();
    }

    public function handle()
    {
        $this->game()->modifiers->each(fn ($modifier) => $modifier->updateModelWithStateData());
    }
}
