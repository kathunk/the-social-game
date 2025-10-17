<?php

namespace App\Events;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasTeam;
use App\Modifiers\Classes\FarmActions;
use App\Modifiers\Classes\FarmMap;
use App\States\GameState;
use App\States\PlayerState;
use App\States\TeamState;
use Thunk\Verbs\Event;

class PlayerHarvestedField extends Event
{
    use HasActivePlayer, HasGame, HasModifier, HasTeam;

    public int $x_index;

    public int $y_index;

    public int $field_quantity;

    public int $player_capacity;

    public int $player_grain;

    public function validate()
    {
        $game = $this->state(GameState::class);

        // Player has actions
        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $player_data = $actions_state->modifier_data[$this->player_id];
        $player_actions = $player_data['actions'];

        $this->assert(
            $player_actions > 0,
            'Player does not have enough actions to harvest field',
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

        // There is a field in the space
        $field_level = $player_space['field_status']['level'] ?? null;
        $this->assert(
            $field_level !== null,
            'There is no field in this space',
        );

        // Field is mature
        $field_stage = $player_space['field_status']['stage'] ?? null;
        $this->assert(
            $field_stage === 'mature',
            'Field is not mature (current stage: '.$field_stage.')',
        );

        // Field is owned by player's team
        $field_owner = $player_space['field_status']['owner_team_id'] ?? null;
        $this->assert(
            $field_owner === $this->team_id,
            'Field is not owned by player\'s team',
        );

        // Player has enough capacity in grain sack
        $player_grain = $this->player_grain;
        $player_capacity = $this->player_capacity;
        $this->assert(
            $player_grain < $player_capacity,
            'Player\'s grain sack is full',
        );

        // Field has enough grain to harvest
        $field_quantity = $player_space['field_status']['quantity'] ?? 0;
        $this->assert(
            $field_quantity > 0,
            'Field has no grain to harvest',
        );
    }

    public function apply(GameState $game)
    {
        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());

        $amount_to_harvest = min($this->field_quantity, $this->player_capacity - $this->player_grain);

        $map_state->modifier_data = collect($map_state->modifier_data)
            ->map(function ($space) use ($amount_to_harvest, $game) {
                if ($space['x-index'] === $this->x_index && $space['y-index'] === $this->y_index) {
                    $space['field_status']['quantity'] = $this->field_quantity - $amount_to_harvest;
                    $space['history'][] = [
                        'round_number' => $game->currentChallenge()->round_number,
                        'emoji' => '🌾',
                        'message' => $this->state(PlayerState::class)->name.' harvested '.$amount_to_harvest.' grain',
                    ];
                }

                if ($space['field_status']['quantity'] === 0) {
                    $space['field_status']['stage'] = null;
                    $space['field_status']['level'] = null;
                    $space['field_status']['owner_team_id'] = null;
                }

                return $space;
            })->toArray();

        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $actions_state->modifier_data = collect($actions_state->modifier_data)
            ->map(function ($data, $player_id) use ($amount_to_harvest) {
                if ($player_id !== $this->player_id) {
                    return $data;
                }

                $data['actions'] = $data['actions'] - 1;
                $data['grain'] = $data['grain'] + $amount_to_harvest;

                return $data;
            })->toArray();
    }

    public function applyToTeam(TeamState $team)
    {
        $amount_to_harvest = min($this->field_quantity, $this->player_capacity - $this->player_grain);
        $team->addToScoreHistory('🌾', $amount_to_harvest, $this->state(PlayerState::class)->name.' harvested '.$amount_to_harvest.' grain.');
    }

    public function handle()
    {
        $this->game()->modifiers->each(fn ($modifier) => $modifier->updateModelWithStateData());
        $this->team()->updateModelWithStateData();
    }
}
