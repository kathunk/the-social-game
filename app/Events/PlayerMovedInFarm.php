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

    public int $x_index;

    public int $y_index;

    public function validate()
    {
        $game = $this->state(GameState::class);
        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());

        $this->assert(
            isset($actions_state->modifier_data[$this->player_id]),
            'Player actions have not been initialized',
        );

        $player_actions = $actions_state->modifier_data[$this->player_id]['actions'];

        // Player has actions
        $this->assert(
            $player_actions > 0,
            'Player does not have enough actions to move',
        );

        // Space is reachable from player space (needs to check for roads)
        // $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());
        // $player_space = collect($map_state->modifier_data)
        //     ->firstWhere(fn ($space) => in_array($this->player_id, $space['player_ids']));

        // $this->assert(
        //     $player_space !== null,
        //     'Player is not currently on any space',
        // );

        // $accessible_spaces = $this->getAccessibleSpaces($player_space, $map_state->modifier_data);
        // $target_coordinate = chr(65 + $this->x_index).($this->y_index + 1);

        // $this->assert(
        //     in_array($target_coordinate, $accessible_spaces),
        //     'Space is not reachable from player\'s current position',
        // );
    }

    protected function getAccessibleSpaces(array $player_space, array $spaces): array
    {
        $player_x = $player_space['x-index'];
        $player_y = $player_space['y-index'];

        $space_map = collect($spaces)->keyBy(function ($space) {
            return $space['x-index'].','.$space['y-index'];
        });

        $player_has_road = ($player_space['road_status']['owner_team_id'] ?? null) !== null;

        $immediate_adjacent = [
            ['x' => $player_x + 1, 'y' => $player_y],
            ['x' => $player_x - 1, 'y' => $player_y],
            ['x' => $player_x, 'y' => $player_y + 1],
            ['x' => $player_x, 'y' => $player_y - 1],
        ];

        $accessible = [];
        $visited = [];

        foreach ($immediate_adjacent as $adj) {
            $adj_key = $adj['x'].','.$adj['y'];

            if (! $space_map->has($adj_key)) {
                continue;
            }

            $adj_space = $space_map->get($adj_key);
            $accessible[$adj_key] = $adj;
            $visited[$adj_key] = true;

            if ($player_has_road && ($adj_space['road_status']['owner_team_id'] ?? null) !== null) {
                $queue = [$adj];

                while (! empty($queue)) {
                    $current = array_shift($queue);
                    $current_key = $current['x'].','.$current['y'];
                    $current_space = $space_map->get($current_key);

                    if (! $current_space || ($current_space['road_status']['owner_team_id'] ?? null) === null) {
                        continue;
                    }

                    $road_adjacent = [
                        ['x' => $current['x'] + 1, 'y' => $current['y']],
                        ['x' => $current['x'] - 1, 'y' => $current['y']],
                        ['x' => $current['x'], 'y' => $current['y'] + 1],
                        ['x' => $current['x'], 'y' => $current['y'] - 1],
                    ];

                    foreach ($road_adjacent as $next) {
                        $next_key = $next['x'].','.$next['y'];

                        if (! isset($visited[$next_key]) && $space_map->has($next_key)) {
                            $visited[$next_key] = true;
                            $accessible[$next_key] = $next;

                            $next_space = $space_map->get($next_key);
                            if (($next_space['road_status']['owner_team_id'] ?? null) !== null) {
                                $queue[] = $next;
                            }
                        }
                    }
                }
            }
        }

        return collect($accessible)->map(function ($coords) {
            return chr(65 + $coords['x']).($coords['y'] + 1);
        })->values()->toArray();
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

                $data['actions'] = $data['actions'] - 1;

                return $data;
            })->toArray();
    }

    public function handle()
    {
        $this->game()->modifiers->each(fn ($modifier) => $modifier->updateModelWithStateData());
    }
}
