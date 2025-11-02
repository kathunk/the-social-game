<?php

namespace App\Modifiers\Classes;

use App\Events\PlayerMovedInFarm;
use App\Models\Game;
use App\Models\Player;
use App\States\GameState;
use App\States\TeamState;
use Thunk\Verbs\Facades\Verbs;

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

    public function dataArrayForState(?Game $game = null): array
    {
        $map_size = 200;
        $spaces_per_row = 10;

        $spaces = collect()->times($map_size, function ($i) use ($spaces_per_row) {
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
                'road_exists' => false,
                'walls_exist' => false,
                'watchtower_exists' => false,
                'trap_status' => [
                    'owner_team_id' => null,
                    'level' => null,
                    'status' => null,
                ],
                'silo_status' => [
                    'level' => null,
                    'owner_team_id' => null,
                    'amount' => 0,
                    'capacity' => 0,
                ],
                'history' => [
                    // [
                    // 'round_number' => 1,
                    // 'emoji' => '🌾',
                    // 'message' => 'Player planted a level 1 farm',
                    // ],
                ],
            ];
        });

        $min_x = 1;
        $max_x = $spaces_per_row - 1;
        $min_y = 1;
        $max_y = $map_size / $spaces_per_row - 1;

        $volcano_x = rand($min_x, $max_x);
        $volcano_y = rand($min_y, $max_y);

        $volcano_is_west = $volcano_x < $max_x / 2;
        $volcano_is_north = $volcano_y < $max_y / 2;

        $tunnel_x = $volcano_is_west
            ? rand($volcano_x + 2, $max_x)
            : rand($min_x, $volcano_x - 2);

        $tunnel_y = $volcano_is_north
            ? rand($volcano_y + 2, $max_y)
            : rand($min_y, $volcano_y - 2);

        $spaces = $spaces->map(function ($space) use ($volcano_x, $volcano_y, $tunnel_x, $tunnel_y) {
            $x = $space['x-index'];
            $y = $space['y-index'];

            if ($x === $tunnel_x && $y === $tunnel_y) {
                $space['type'] = 'tunnel';
            }

            if ($x === $volcano_x && $y === $volcano_y) {
                $space['type'] = 'volcano';
                $explosion_count = rand(2, 4);
                $space['explodes_on_rounds'] = collect(range(1, 28))->shuffle()->take($explosion_count)->toArray();
            }

            if ($x === $volcano_x && ($y === $volcano_y + 1 || $y === $volcano_y - 1)) {
                $space['type'] = 'fertile_ashland';
            }

            if ($y === $volcano_y && ($x === $volcano_x + 1 || $x === $volcano_x - 1)) {
                $space['type'] = 'fertile_ashland';
            }

            return $space;
        });

        return $spaces->toArray();
    }

    public function frontendComponent(Player $player): array
    {
        // dd([
        //     'volcano' => collect($this->modifier->modifier_data)->filter(fn ($space) => $space['type'] === 'volcano')->first(),
        //     'tunnel' => collect($this->modifier->modifier_data)->filter(fn ($space) => $space['type'] === 'tunnel')->first(),
        // ]);

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
                actions: $actions,
                player_space: $player_space,
                accessible_spaces: $accessible_spaces,
                scoutable_spaces: $this->scoutableSpaces($player_space, $player_skills),
            )
            ->build();
    }

    public function frontendComponentForDedicatedPage(Player $player): array
    {
        $limit_of_rounds_to_show = match ($this->playerSkills($player)['Scout']) {
            1 => 2,
            2 => 5,
            3 => null,
        };

        $current_round_number = $this->modifier->game->currentChallenge->round_number;
        $first_round_number = $limit_of_rounds_to_show
            ? max(1, $current_round_number - $limit_of_rounds_to_show + 1)
            : 1;

        $round_numbers_to_show = range($first_round_number, $current_round_number);

        $history = collect($this->playerSpace($player)['history']);

        if ($limit_of_rounds_to_show) {
            $history = $history->filter(fn ($event) => in_array($event['round_number'], $round_numbers_to_show));
        }

        $form = $this->form()
            ->title('Space History')
            ->when($limit_of_rounds_to_show !== null, fn ($form) => $form->subtitle('Showing up to last '.$limit_of_rounds_to_show.' rounds of history'))
            ->when($limit_of_rounds_to_show === null, fn ($form) => $form->subtitle('Showing complete history'));

        foreach ($round_numbers_to_show as $round_number) {
            $form->divider()
                ->title('Round '.$round_number);

            $events = $history->filter(fn ($event) => $event['round_number'] === $round_number);

            if ($events->isEmpty()) {
                $form->subtitle('No events for round '.$round_number);

                continue;
            }

            foreach ($events as $event) {
                $form->subtitle($event['emoji'].' '.$event['message']);
            }
        }

        return $form->build();
    }

    public function onChallengeEnded(GameState $game_state)
    {
        $modifier_state = $game_state->modifiers()->firstWhere('class_key', self::key());

        $volcano_space = collect($modifier_state->modifier_data)->filter(fn ($space) => $space['type'] === 'volcano')->first();
        $explosion = in_array($game_state->currentChallenge()->round_number, $volcano_space['explodes_on_rounds']);

        $modifier_state->modifier_data = collect($modifier_state->modifier_data)->map(function ($space) use ($explosion, $game_state) {
            $new_field_stage = match ($space['field_status']['stage'] ?? null) {
                'seedlings' => 'sprouts',
                'sprouts' => 'mature_1',
                'mature_1' => 'mature_2',
                'mature_2' => 'mature_3',
                'mature_3' => 'rotted',
                'rotted' => null,
                null => null,
            };

            $new_field_quantity = match ($new_field_stage) {
                'mature_1' => match ($space['field_status']['level']) {
                    1 => 5,
                    2 => 10,
                    3 => 15,
                },
                'mature_2' => $space['field_status']['quantity'],
                'mature_3' => $space['field_status']['quantity'],
                default => 0,
                null => 0,
            };

            if ($space['type'] === 'fertile_ashland') {
                $new_field_quantity = $new_field_quantity * 2;
            }

            $space['field_status'] = [
                'level' => $new_field_stage === null ? null : $space['field_status']['level'],
                'owner_team_id' => $new_field_stage === null ? null : $space['field_status']['owner_team_id'],
                'stage' => $new_field_stage,
                'quantity' => $new_field_quantity,
            ];

            if (! in_array($space['type'], ['fertile_ashland', 'ash_heap'])) {
                return $space;
            }

            if ($explosion) {
                $field_level = $space['field_status']['level'];
                $field_quantity = $space['field_status']['quantity'];
                $silo_level = $space['silo_status']['level'];
                $silo_amount = $space['silo_status']['amount'];
                $road_exists = $space['road_exists'];
                $walls_exist = $space['walls_exist'];
                $watchtower_exists = $space['watchtower_exists'];
                $trap_status = $space['trap_status'];

                if ($field_level) {
                    $field_owner = TeamState::load($space['field_status']['owner_team_id']);
                    $space['history'][] = [
                        'round_number' => $game_state->currentChallenge()->round_number,
                        'emoji' => '🌋',
                        'message' => 'Volcano destroyed a level '.$field_level.' field with '.$field_quantity.' grain owned by '.$field_owner->name.'.',
                    ];
                }

                if ($silo_level) {
                    $silo_owner = TeamState::load($space['silo_status']['owner_team_id']);
                    $space['history'][] = [
                        'round_number' => $game_state->currentChallenge()->round_number,
                        'emoji' => '🌋',
                        'message' => 'Volcano destroyed a level '.$silo_level.' silo with '.$silo_amount.' grain owned by '.$silo_owner->name.'.',
                    ];

                    $silo_owner->addToScoreHistory('🌋', -$silo_amount, 'Volcano destroyed a level '.$silo_level.' silo with '.$silo_amount.' grain with it.');
                }

                if ($road_exists) {
                    $space['history'][] = [
                        'round_number' => $game_state->currentChallenge()->round_number,
                        'emoji' => '🌋',
                        'message' => 'Volcano destroyed a road.',
                    ];
                }

                if ($walls_exist) {
                    $space['history'][] = [
                        'round_number' => $game_state->currentChallenge()->round_number,
                        'emoji' => '🌋',
                        'message' => 'Volcano destroyed a wall.',
                    ];
                }

                if ($watchtower_exists) {
                    $space['history'][] = [
                        'round_number' => $game_state->currentChallenge()->round_number,
                        'emoji' => '🌋',
                        'message' => 'Volcano destroyed a watchtower.',
                    ];
                }

                if ($trap_status['owner_team_id'] !== null) {
                    $trap_owner = TeamState::load($trap_status['owner_team_id']);
                    $space['history'][] = [
                        'round_number' => $game_state->currentChallenge()->round_number,
                        'emoji' => '🌋',
                        'message' => 'Volcano destroyed a trap owned by '.$trap_owner->name.'.',
                    ];
                }

                $space['field_status']['quantity'] = 0;
                $space['field_status']['stage'] = null;
                $space['field_status']['level'] = null;
                $space['field_status']['owner_team_id'] = null;

                $space['silo_status']['level'] = null;
                $space['silo_status']['owner_team_id'] = null;
                $space['silo_status']['amount'] = 0;
                $space['silo_status']['capacity'] = 0;

                $space['road_exists'] = false;
                $space['walls_exist'] = false;
                $space['watchtower_exists'] = false;
                $space['trap_status']['owner_team_id'] = null;
                $space['trap_status']['level'] = null;
                $space['trap_status']['status'] = null;
            }

            if (! $explosion && $space['type'] === 'ash_heap') {
                $space['type'] = 'fertile_ashland';
            }

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

    public function scoutableSpaces(?array $player_space, array $player_skills)
    {
        if ($player_space === null) {
            return [];
        }

        $scout_distance = $player_skills['Scout'] + 1;

        if ($player_space['watchtower_exists']) {
            $scout_distance = $scout_distance + 3;
        }

        $player_x = $player_space['x-index'];
        $player_y = $player_space['y-index'];

        // return spaces that are within scout distance using Manhattan distance (no diagonals)
        return collect($this->modifier->modifier_data)->filter(function ($space) use ($player_x, $player_y, $scout_distance) {
            $dx = abs($space['x-index'] - $player_x);
            $dy = abs($space['y-index'] - $player_y);

            // Manhattan distance: sum of absolute differences
            $manhattan_distance = $dx + $dy;

            return $manhattan_distance > 0 && $manhattan_distance <= $scout_distance;
        })->toArray();
    }

    public function accessibleSpaces(Player $player, array $spaces, ?array $player_space = null)
    {
        if ($player_space === null) {
            return [];
        }

        if ($player_space['type'] === 'tunnel') {
            // return all spaces, except the player's space
            return collect($spaces)
                ->filter(fn ($space) => $space['x-index'] !== $player_space['x-index'] || $space['y-index'] !== $player_space['y-index'])
                ->mapWithKeys(function ($space) {
                    return [chr(65 + $space['x-index']).($space['y-index'] + 1) => chr(65 + $space['x-index']).($space['y-index'] + 1)];
                })->toArray();
        }

        $player_x = $player_space['x-index'];
        $player_y = $player_space['y-index'];

        // Create a map for quick space lookup by coordinates
        $space_map = collect($spaces)->keyBy(function ($space) {
            return $space['x-index'].','.$space['y-index'];
        });

        $immediate_adjacent = [
            ['x' => $player_x + 1, 'y' => $player_y],
            ['x' => $player_x - 1, 'y' => $player_y],
            ['x' => $player_x, 'y' => $player_y + 1],
            ['x' => $player_x, 'y' => $player_y - 1],
        ];

        // Convert immediate adjacent spaces to pretty format
        return collect($immediate_adjacent)->mapWithKeys(function ($coords) use ($space_map) {
            $pretty = chr(65 + $coords['x']).($coords['y'] + 1);

            $space = $space_map->get($coords['x'].','.$coords['y']);

            return [$pretty => $pretty];
        })->toArray();
    }

    // actions

    public function move(Player $player, array $params)
    {
        $space_string = $params['selected_space'];

        $x = ord($space_string[0]) - 65;
        $y = intval(substr($space_string, 1)) - 1;

        $space = collect($this->modifier->modifier_data)
            ->filter(fn ($space) => $space['x-index'] === $x && $space['y-index'] === $y)
            ->first();

        if (! $space) {
            throw new \Exception('Space not found');
        }

        $origin_space = $this->playerSpace($player);

        PlayerMovedInFarm::fire(
            game_id: $this->modifier->game_id,
            modifier_id: $this->modifier->id,
            player_id: $player->id,
            origin_space: $origin_space,
            destination_space: $space,
        );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    public static function costToMove(?array $origin_space, array $destination_space)
    {
        if ($origin_space === null) {
            return 0;
        }

        $cost = 1;

        $cost += match ($destination_space['type']) {
            'mountain', 'swamp' => 1,
            default => 0,
        } + match ($destination_space['walls_exist']) {
            true => 1,
            default => 0,
        } + match ($origin_space['road_exists']) {
            true => -1,
            default => 0,
        };

        return max(0, $cost);
    }
}
