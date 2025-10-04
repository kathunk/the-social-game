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

    public function onGameStarted(
        GameState $game_state,
        ModifierState $modifier_state,
    ) {
        $game_state->player_ids->each(function ($player_id) use ($modifier_state) {
            $this->putPlayerInRandomSpace($modifier_state, $player_id);
        });
    }

    public function onUserAdmittedToGame(
        PlayerState $player_state,
        GameState $game_state,
        ModifierState $modifier_state,
    ) { 
        $this->putPlayerInRandomSpace($modifier_state, $player_state->id);
    }

    public function putPlayerInRandomSpace(ModifierState $modifier_state, int $player_id)
    {
        $spaces = collect($modifier_state->modifier_data);
        $random_space = $spaces->random();
        $spaces = collect($spaces)->map(function ($space) use ($random_space, $player_id) {
            if ($space['x-index'] === $random_space['x-index'] && $space['y-index'] === $random_space['y-index']) {
                $space['player_ids'][] = $player_id;
            }

            return $space;
        })->toArray();
        $modifier_state->modifier_data = $spaces;
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
