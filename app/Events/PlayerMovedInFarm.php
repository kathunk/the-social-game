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

    public int $origin_x_index;

    public int $origin_y_index;

    public int $origin_space_type;

    public bool $origin_space_has_walls;

    public bool $origin_space_has_road;

    public bool $origin_space_has_field;

    public int $x_index;

    public int $y_index;

    public int $destination_space_type;

    public bool $destination_space_has_walls;

    public bool $destination_space_has_road;

    public bool $destination_space_has_field;

    // only used for computation

    private int $action_cost = 1;

    public function validate()
    {
        $game = $this->state(GameState::class);
        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());

        $this->assert(
            isset($actions_state->modifier_data[$this->player_id]),
            'Player actions have not been initialized',
        );

        $player_actions = $actions_state->modifier_data[$this->player_id]['actions'];

        $this->action_cost += match ($this->destination_space_type) {
            'mountain', 'swamp' => 1,
            default => 0,
        } + match ($this->destination_space_has_walls) {
            true => 1,
            default => 0,
        } + match ($this->origin_space_has_road) {
            true => 1,
            default => 0,
        };

        $this->action_cost = max(0, $this->action_cost);

        $this->assert(
            $player_actions >= $this->action_cost,
            'Player does not have enough actions to move',
        );
    }

    public function apply(GameState $game)
    {
        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());

        $map_state->modifier_data = collect($map_state->modifier_data)->map(function ($space) {
            if ($space['x-index'] === $this->x_index && $space['y-index'] === $this->y_index) {
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
