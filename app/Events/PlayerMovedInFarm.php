<?php

namespace App\Events;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Modifiers\Classes\FarmActions;
use App\Modifiers\Classes\FarmMap;
use App\States\GameState;
use Thunk\Verbs\Event;

class PlayerMovedInFarm extends Event
{
    use HasActivePlayer, HasGame, HasModifier;

    public ?array $origin_space = null;

    public array $destination_space;

    // only used for computation
    private int $action_cost = 0;

    public function validate()
    {
        $farm_map_state = $this->state(GameState::class)->modifiers()->firstWhere('class_key', FarmMap::key());
        $real_spaces = $farm_map_state->modifier_data;
        $space_containing_player = collect($real_spaces)
            ->filter(fn ($s) => in_array($this->player_id, $s['player_ids']))
            ->first();

        if ($this->origin_space === null) {
            return;
        }

        $this->assert(
            $space_containing_player['x-index'] === $this->origin_space['x-index'] && $space_containing_player['y-index'] === $this->origin_space['y-index'],
            'Player is not in the origin space',
        );

        // the origin space is adjacent to the destination space, or the origin space is a tunnel
        $origin_is_tunnel = $this->origin_space['type'] === 'tunnel';
        $origin_is_adjacent_to_destination =
            ($this->origin_space['x-index'] === $this->destination_space['x-index'] && abs($this->origin_space['y-index'] - $this->destination_space['y-index']) === 1) ||
            ($this->origin_space['y-index'] === $this->destination_space['y-index'] && abs($this->origin_space['x-index'] - $this->destination_space['x-index']) === 1);

        $this->assert(
            $origin_is_tunnel || $origin_is_adjacent_to_destination,
            'Origin space is not adjacent to destination space',
        );

        $actions_state = $this->state(GameState::class)->modifiers()->firstWhere('class_key', FarmActions::key());
        $player_actions = $actions_state->modifier_data[$this->player_id]['actions'];

        $this->action_cost = max(0, FarmMap::costToMove($this->origin_space, $this->destination_space));

        $this->assert(
            $player_actions >= $this->action_cost,
            'Player does not have enough actions to move',
        );
    }

    public function apply(GameState $game)
    {
        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());

        $map_state->modifier_data = collect($map_state->modifier_data)->map(function ($space) {
            if ($space['x-index'] === $this->destination_space['x-index'] && $space['y-index'] === $this->destination_space['y-index']) {
                $space['player_ids'][] = $this->player_id;
            } else {
                $space['player_ids'] = array_values(array_diff($space['player_ids'], [$this->player_id]));
            }

            return $space;
        })->toArray();

        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $actions_state->modifier_data = collect($actions_state->modifier_data)
            ->map(function ($data, $player_id) {
                if ($player_id !== $this->player_id) {
                    return $data;
                }

                $data['actions'] = $data['actions'] - $this->action_cost;

                return $data;
            })->toArray();
    }

    public function handle()
    {
        $this->game()->modifiers->each(fn ($modifier) => $modifier->updateModelWithStateData());
    }
}
