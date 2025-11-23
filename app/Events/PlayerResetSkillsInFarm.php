<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\GameState;
use App\States\ModifierState;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Modifiers\Classes\FarmSkills;
use App\Events\Traits\HasActivePlayer;
use App\Modifiers\Classes\FarmActions;

class PlayerResetSkillsInFarm extends Event
{
    use HasActivePlayer, HasGame, HasModifier;

    public function validate()
    {
        $game = $this->state(GameState::class);
        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $player_actions = $actions_state->modifier_data[$this->player_id];

        $player_grain = $player_actions['grain'];
        $this->assert(
            $player_grain === 20,
            'Player does not have 20 grain to reset skills',
        );
    }

    public function apply(ModifierState $modifier)
    {
        $modifier->modifier_data = collect($modifier->modifier_data)
            ->map(function ($data, $player_id) {
                if ($player_id !== $this->player_id) {
                    return $data;
                }

                $data['grain'] -= 20;

                return $data;
            })
            ->toArray();

        $skills_state = $this->state(GameState::class)->modifiers()->firstWhere('class_key', FarmSkills::key());

        $skills_state->modifier_data = collect($skills_state->modifier_data)
            ->map(function ($data, $player_id) {
                if ($player_id !== $this->player_id) {
                    return $data;
                }

                foreach ($data['skills'] as $skill_name => $skill_level) {
                    $data['xp'] += match ($skill_level) {
                        0 => 0,
                        1 => 1,
                        2 => 4,
                        3 => 9,
                    };

                    $data['skills'][$skill_name] = 0;
                }

                return $data;
            })
            ->toArray();
    }

    public function handle()
    {
        $this->game()->modifiers->each(fn ($modifier) => $modifier->updateModelWithStateData());
    }
}
