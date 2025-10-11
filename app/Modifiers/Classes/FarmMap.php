<?php

namespace App\Modifiers\Classes;

use App\Models\Player;
use App\States\GameState;
use Thunk\Verbs\Facades\Verbs;
use App\Events\PlayerBuiltRoad;
use App\Events\PlayerMovedInFarm;

class FarmMap extends BaseModifierClass
{
    const NAME = 'Farm Map';

    const DESCRIPTION = 'The map for the farm game.';

    const TYPE = 'team';

    public static function key(): string
    {
        return 'farm_map';
    }

    public function isInvalidForTemplate(
        array $challenges,
        array $modifiers,
        string $type,
        array $team_names
    ) {
        if (! in_array(FarmTeams::key(), $modifiers)) {
            return 'Farm teams modifier is required to run this modifier';
        }

        return false;
    }

    public function dataArrayForState(): array
    {
        // @farmtodo: can't have randomness in this array
        return collect()->times(100, function ($i) {
            $spaces_per_row = 10;
            $y = intdiv($i - 1, $spaces_per_row);
            $x = ($i - 1) % $spaces_per_row;
        
            return [
                'y-index' => $y,
                'x-index' => $x,
                'type' => collect(['grass', 'desert', 'swamp', 'mountain'])->random(),
                'player_ids' => [],
                'field_status' => [
                    'owner_team_id' => null,
                    'level' => null,
                    'stage' => null,
                    'quantity' => 0,
                ],
                'road_status' => [
                    'level' => null,
                    'owner_team_id' => null,
                ],
                'silo_status' => [
                    'level' => null,
                    'owner_team_id' => null,
                    'amount' => 0,
                    'capacity' => 0,
                ],
            ];
        })->toArray();
    }

    public function frontendComponent(Player $player): array
    {
        if ($player->team_id === null) {
            return [];
        }

        $actions = $this->playerActions($player);
        $player_space = $this->playerSpace($player);
        $accessible_spaces = $this->accessibleSpaces($player, $this->modifier->modifier_data, $player_space);
        $player_skills = $this->playerSkills($player);
        
        return $this->form()
            ->farmMap(
                spaces: $this->modifier->modifier_data,
                player: $player,
                player_space: $player_space,
                accessible_spaces: $accessible_spaces,
                can_move: $this->canMove($player, $actions),
                can_build_road: $this->canBuildRoad($player, $player_skills, $player_space, $actions),
            )
            ->build();
    }

    public function onChallengeEnded(GameState $game_state)
    {
        $modifier_state = $game_state->modifiers()->firstWhere('class_key', self::key());

        $modifier_state->modifier_data = collect($modifier_state->modifier_data)->map(function ($space) {
            $new_field_stage = match ($space['field_status']['stage'] ?? null) {
                'seedlings' => 'sprouts',
                'sprouts' => 'mature',
                'mature' => 'rotted',
                'rotted' => null,
                null => null,
            };

            $new_field_quantity = match ($new_field_stage) {
                'sprouts' => 0,
                'mature' => match ($space['field_status']['level']) {
                    1 => 5,
                    2 => 10,
                    3 => 15,
                },
                'rotted' => 0,
                null => 0,
            };

            $space['field_status'] = [
                'level' => $new_field_stage === null ? null : $space['field_status']['level'],
                'owner_team_id' => $new_field_stage === null ? null : $space['field_status']['owner_team_id'],
                'stage' => $new_field_stage,
                'quantity' => $new_field_quantity,
            ];

            return $space;
        })->toArray();
    }

    // data helpers

    public function playerActions(Player $player)
    {
        return $this->modifier->game->modifiers->firstWhere('class_key', FarmActions::key())->modifier_data[$player->id]['actions'];
    }

    public function playerSpace(Player $player)
    {
        return collect($this->modifier->modifier_data)->filter(fn ($space) => in_array($player->id, $space['player_ids']))->first();
    }

    public function playerSkills(Player $player)
    {
        return $this->modifier->game->modifiers->firstWhere('class_key', FarmSkills::key())->modifier_data[$player->id]['skills'];
    }

    public function accessibleSpaces(Player $player, array $spaces, array $player_space)
    {
        $player_x = $player_space['x-index'];
        $player_y = $player_space['y-index'];

        // Create a map for quick space lookup by coordinates
        $space_map = collect($spaces)->keyBy(function ($space) {
            return $space['x-index'] . ',' . $space['y-index'];
        });

        // Check if player's current space has a road
        $player_has_road = ($player_space['road_status']['owner_team_id'] ?? null) !== null;

        // Get all spaces that are adjacent to player's current position
        $immediate_adjacent = [
            ['x' => $player_x + 1, 'y' => $player_y],
            ['x' => $player_x - 1, 'y' => $player_y],
            ['x' => $player_x, 'y' => $player_y + 1],
            ['x' => $player_x, 'y' => $player_y - 1],
        ];

        $accessible = [];
        $visited = [];

        // For each immediately adjacent space
        foreach ($immediate_adjacent as $adj) {
            $adj_key = $adj['x'] . ',' . $adj['y'];

            if (!$space_map->has($adj_key)) {
                continue;
            }

            $adj_space = $space_map->get($adj_key);

            // Always add immediately adjacent spaces
            $accessible[$adj_key] = $adj;
            $visited[$adj_key] = true;

            // Only explore road network if PLAYER's current space has a road AND adjacent space has a road
            if ($player_has_road && ($adj_space['road_status']['owner_team_id'] ?? null) !== null) {
                $queue = [$adj];

                while (!empty($queue)) {
                    $current = array_shift($queue);
                    $current_key = $current['x'] . ',' . $current['y'];
                    $current_space = $space_map->get($current_key);

                    if (!$current_space) {
                        continue;
                    }

                    // Only continue if current space has a road
                    if (($current_space['road_status']['owner_team_id'] ?? null) === null) {
                        continue;
                    }

                    // Get spaces adjacent to this road space
                    $road_adjacent = [
                        ['x' => $current['x'] + 1, 'y' => $current['y']],
                        ['x' => $current['x'] - 1, 'y' => $current['y']],
                        ['x' => $current['x'], 'y' => $current['y'] + 1],
                        ['x' => $current['x'], 'y' => $current['y'] - 1],
                    ];

                    foreach ($road_adjacent as $next) {
                        $next_key = $next['x'] . ',' . $next['y'];

                        if (!isset($visited[$next_key]) && $space_map->has($next_key)) {
                            $visited[$next_key] = true;
                            $accessible[$next_key] = $next;

                            $next_space = $space_map->get($next_key);

                            // Only add to queue if it has a road (to continue exploring)
                            if (($next_space['road_status']['owner_team_id'] ?? null) !== null) {
                                $queue[] = $next;
                            }
                        }
                    }
                }
            }
        }

        // Convert accessible spaces to pretty format
        return collect($accessible)->mapWithKeys(function ($coords) {
            $pretty = chr(65 + $coords['x']) . ($coords['y'] + 1);
            return [$pretty => $pretty];
        })->toArray();
    }

    // actions

    public function canMove(Player $player, int $actions)
    {
        return $actions > 0;
    }

    public function move(Player $player, array $params)
    {
        $space_string = $params['selected_space'];

        $x = ord($space_string[0]) - 65;
        $y = intval($space_string[1]) - 1;

        $space = collect($this->modifier->modifier_data)
            ->filter(fn ($space) => $space['x-index'] === $x && $space['y-index'] === $y)
            ->first();

        if (! $space) {
            throw new \Exception('Space not found');
        }

        PlayerMovedInFarm::fire(
            game_id: $this->modifier->game_id,
            modifier_id: $this->modifier->id,
            player_id: $player->id,
            x_index: $x,
            y_index: $y,
        );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    public function canBuildRoad(Player $player, array $player_skills, array $player_space, int $actions)
    {
        return $actions > 0
            && $player_skills['Builder'] > 2
            && ($player_space['road_status']['owner_team_id'] ?? null) === null
            && ($player_space['type'] !== 'swamp')
            && ($player_space['type'] !== 'mountain');
    }

    public function buildRoad(Player $player, array $params)
    {
        $player_space = $this->playerSpace($player);
        
        PlayerBuiltRoad::fire(
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            player_id: $player->id,
            team_id: $player->team_id,
            x_index: $player_space['x-index'],
            y_index: $player_space['y-index'],
            level: 1,
        );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }
}
