<?php

namespace App\Events\Farm;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasTeam;
use App\Modifiers\Classes\Farm\FarmActions;
use App\Modifiers\Classes\Farm\FarmMap;
use App\States\GameState;
use App\States\PlayerState;
use Thunk\Verbs\Event;

class PlayerDepositedToSilo extends Event
{
    use HasActivePlayer, HasGame, HasModifier, HasTeam;

    public int $x_index;

    public int $y_index;

    public int $amount;

    public function validate()
    {
        $game = $this->state(GameState::class);
        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $player_data = $actions_state->modifier_data[$this->player_id];

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

        // Player has enough grain in grain sack
        $player_grain = $player_data['grain'];
        $this->assert(
            $player_grain >= $this->amount,
            'Player does not have enough grain (has '.$player_grain.', needs '.$this->amount.')',
        );

        // There is a silo in the space
        $silo_level = $player_space['silo_status']['level'] ?? null;
        $this->assert(
            $silo_level !== null && $silo_level > 0,
            'There is no silo in this space',
        );

        // Silo is owned by player's team
        $silo_owner = $player_space['silo_status']['owner_team_id'] ?? null;
        $this->assert(
            $silo_owner === $this->team_id,
            'Silo is not owned by player\'s team',
        );

        // Silo has enough capacity
        $silo_amount = $player_space['silo_status']['amount'];
        $silo_capacity = $player_space['silo_status']['capacity'];
        $this->assert(
            $silo_amount + $this->amount <= $silo_capacity,
            'Silo does not have enough capacity (has '.$silo_amount.'/'.$silo_capacity.', trying to deposit '.$this->amount.')',
        );
    }

    public function apply(GameState $game)
    {
        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());

        $map_state->modifier_data = collect($map_state->modifier_data)->map(function ($space) use ($game) {
            if ($space['x-index'] === $this->x_index && $space['y-index'] === $this->y_index) {
                $space['silo_status']['amount'] += $this->amount;
                $space['history'][] = [
                    'round_number' => $game->currentChallenge()->round_number,
                    'emoji' => '📈',
                    'message' => $this->state(PlayerState::class)->name.' deposited '.$this->amount.' grain to silo',
                ];
            }

            return $space;
        })->toArray();

        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $actions_state->modifier_data = collect($actions_state->modifier_data)
            ->map(function ($data, $player_id) {
                if ($player_id !== $this->player_id) {
                    return $data;
                }

                $data['grain'] -= $this->amount;

                return $data;
            })->toArray();
    }

    public function handle()
    {
        $this->game()->modifiers->each(fn ($modifier) => $modifier->updateModelWithStateData());
    }
}
