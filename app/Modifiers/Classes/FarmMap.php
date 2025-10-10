<?php

namespace App\Modifiers\Classes;

use App\Models\Player;
use App\States\GameState;
use App\States\PlayerState;
use App\States\ModifierState;
use App\States\ChallengeState;

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
        
        return $this->form()
            ->farmMap($this->modifier->modifier_data, $player)
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
}
