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
        // player has actions
        // player is in correct space
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
