<?php

namespace App\Events\Farm;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasTeam;
use App\Modifiers\Farm\FarmActions;
use App\Modifiers\Farm\FarmMap;
use App\Modifiers\Farm\FarmSkills;
use App\States\GameState;
use App\States\PlayerState;
use Thunk\Verbs\Event;

class PlayerBuiltTrapInFarm extends Event
{
    use HasActivePlayer, HasGame, HasModifier, HasTeam;

    public int $x_index;

    public int $y_index;

    public int $level;

    public function validate()
    {
        $game = $this->state(GameState::class);

        // Player has actions
        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $player_actions = $actions_state->modifier_data[$this->player_id]['actions'];
        $player_skills = $game->modifiers()->firstWhere('class_key', FarmSkills::key())->modifier_data[$this->player_id]['skills'];
        $action_cost = 4 - $player_skills['Builder'];

        $this->assert(
            $player_actions >= $action_cost,
            'Player does not have enough actions to build trap',
        );

        // Player is in correct space
        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());
        $player_space = collect($map_state->modifier_data)
            ->firstWhere(fn ($space) => in_array($this->player_id, $space['player_ids']));

        $this->assert(
            $player_space !== null,
            'Player is not currently on any space',
        );

        $this->assert(
            $player_space['x-index'] === $this->x_index && $player_space['y-index'] === $this->y_index,
            'Player is not on the specified space',
        );

        $this->assert(
            ($player_space['trap_status']['level'] ?? null) === null || $player_space['trap_status']['level'] === 0,
            'Space already has a trap',
        );
    }

    public function apply(GameState $game)
    {
        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());

        $map_state->modifier_data = collect($map_state->modifier_data)->map(function ($space) use ($game) {
            if ($space['x-index'] === $this->x_index && $space['y-index'] === $this->y_index) {
                $space['trap_status']['level'] = $this->level;
                $space['trap_status']['owner_team_id'] = $this->team_id;
                $space['trap_status']['status'] = 'set';
                $space['history'][] = [
                    'round_number' => $game->currentChallenge()->round_number,
                    'emoji' => '🪤',
                    'message' => $this->state(PlayerState::class)->name.' built a trap',
                ];
            }

            return $space;
        })->toArray();

        $player_skills = $game->modifiers()->firstWhere('class_key', FarmSkills::key())
            ->modifier_data[$this->player_id]['skills'];

        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $actions_state->modifier_data = collect($actions_state->modifier_data)
            ->map(function ($data, $player_id) use ($player_skills) {
                if ($player_id !== $this->player_id) {
                    return $data;
                }

                $builder_level = $player_skills['Builder'];
                $action_cost = 4 - $builder_level;

                $data['actions'] = $data['actions'] - $action_cost;

                return $data;
            })->toArray();
    }

    public function handle()
    {
        $this->game()->modifiers->each(fn ($modifier) => $modifier->updateModelWithStateData());
    }
}
