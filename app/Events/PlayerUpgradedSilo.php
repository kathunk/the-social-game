<?php

namespace App\Events;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasTeam;
use App\Modifiers\Classes\FarmActions;
use App\Modifiers\Classes\FarmMap;
use App\Modifiers\Classes\FarmSkills;
use App\States\GameState;
use Thunk\Verbs\Event;

class PlayerUpgradedSilo extends Event
{
    use HasActivePlayer, HasGame, HasModifier, HasTeam;

    public int $x_index;

    public int $y_index;

    public int $level;

    public function validate()
    {
        $game = $this->state(GameState::class);
        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $player_actions = $actions_state->modifier_data[$this->player_id]['actions'];

        // Player has actions
        $this->assert(
            $player_actions > 0,
            'Player does not have enough actions to upgrade silo',
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

        // Player builder level matches $this->level
        $skills_state = $game->modifiers()->firstWhere('class_key', FarmSkills::key());
        $player_builder_level = $skills_state->modifier_data[$this->player_id]['skills']['Builder'];

        $this->assert(
            $player_builder_level >= $this->level,
            'Player\'s Builder skill level is not high enough to upgrade silo to level '.$this->level,
        );

        // Space has a silo owned by player's team
        $target_space = collect($map_state->modifier_data)
            ->firstWhere(fn ($space) => $space['x-index'] === $this->x_index && $space['y-index'] === $this->y_index);

        $this->assert(
            $target_space !== null && ($target_space['silo_status']['owner_team_id'] ?? null) !== null,
            'Space does not have a silo',
        );

        $this->assert(
            $target_space['silo_status']['owner_team_id'] === $this->team_id,
            'Silo is not owned by player\'s team',
        );

        // Silo is not already at $this->level
        $current_silo_level = $target_space['silo_status']['level'] ?? 0;

        $this->assert(
            $current_silo_level < $this->level,
            'Silo is already at or above level '.$this->level,
        );
    }

    public function apply(GameState $game)
    {
        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());

        $map_state->modifier_data = collect($map_state->modifier_data)->map(function ($space) {
            if ($space['x-index'] === $this->x_index && $space['y-index'] === $this->y_index) {
                $space['silo_status']['level'] = $this->level;
                $space['silo_status']['capacity'] = 20 * $this->level;
            }

            return $space;
        })->toArray();

        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $actions_state->modifier_data = collect($actions_state->modifier_data)
            ->map(function ($data, $player_id) {
                if ($player_id !== $this->player_id) {
                    return $data;
                }

                $data['actions'] = $data['actions'] - 1;

                return $data;
            })->toArray();
    }

    public function handle()
    {
        $this->game()->modifiers->each(fn ($modifier) => $modifier->updateModelWithStateData());
    }
}
