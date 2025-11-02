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
use App\States\PlayerState;
use Thunk\Verbs\Event;

class PlayerBuiltWatchtower extends Event
{
    use HasActivePlayer, HasGame, HasModifier, HasTeam;

    public int $x_index;

    public int $y_index;

    public function validate()
    {
        $game = $this->state(GameState::class);

        // Player has actions
        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());

        $this->assert(
            isset($actions_state->modifier_data[$this->player_id]),
            'Player actions have not been initialized',
        );

        // @farmtodo ensure they have the actions for it
        // @farmtodo ensure a scout ally is present

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

        // Space had no watchtower
        $this->assert(
            ($player_space['watchtower_exists'] ?? false) === false,
            'Space already has a watchtower',
        );

        $valid_types = ['grass', 'fertile_ashland', 'mountain', 'desert'];
        $this->assert(
            in_array($player_space['type'], $valid_types),
            'Can only build watchtowers on '.implode(', ', $valid_types).', not '.$player_space['type'],
        );
    }

    public function apply(GameState $game)
    {
        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());

        $map_state->modifier_data = collect($map_state->modifier_data)->map(function ($space) use ($game) {
            if ($space['x-index'] === $this->x_index && $space['y-index'] === $this->y_index) {
                $space['watchtower_exists'] = true;
                $space['history'][] = [
                    'round_number' => $game->currentChallenge()->round_number,
                    'emoji' => '🗼',
                    'message' => $this->state(PlayerState::class)->name.' built a watchtower',
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
