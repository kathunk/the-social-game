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
use App\States\TeamState;
use Thunk\Verbs\Event;

class PlayerDepositedToStash extends Event
{
    use HasActivePlayer, HasGame, HasModifier, HasTeam;

    public int $x_index;

    public int $y_index;

    public int $amount;

    public function validate()
    {
        $game = $this->state(GameState::class);
        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());
        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $player_data = $actions_state->modifier_data[$this->player_id];

        // Player is in correct space
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

        // Stash is in space
        $stash_exists = $player_space['stash_status']['amount'] > 0 || $player_space['stash_status']['player_owner_id'] !== null;
        $this->assert(
            $stash_exists,
            'There is no stash in this space',
        );
    }

    public function apply(GameState $game)
    {
        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());
        $player_state = $this->state(PlayerState::class);
        $team_state = $this->state(TeamState::class);
        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $player_data = $actions_state->modifier_data[$this->player_id];
        $stash_data = collect($map_state->modifier_data)
            ->filter(fn ($space) => $space['x-index'] === $this->x_index && $space['y-index'] === $this->y_index)
            ->first()['stash_status'];

        $stash_owner_player = PlayerState::load($stash_data['player_owner_id']);

        $map_state->modifier_data = collect($map_state->modifier_data)->map(function ($space) use ($game) {
            if ($space['x-index'] === $this->x_index && $space['y-index'] === $this->y_index) {
                $space['stash_status']['amount'] += $this->amount;
                $space['history'][] = [
                    'round_number' => $game->currentChallenge()->round_number,
                    'emoji' => '📈',
                    'message' => $this->state(PlayerState::class)->name.' hid '.$this->amount.' grain in a hidden stash',
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
        $this->game()->teams->each(fn ($team) => $team->updateModelWithStateData());
    }
}
