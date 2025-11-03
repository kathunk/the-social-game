<?php

namespace App\Events;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasTeam;
use App\Modifiers\Classes\FarmActions;
use App\Modifiers\Classes\FarmMap;
use App\Modifiers\Classes\FarmSkills;
use App\States\GameState;
use App\States\PlayerState;
use Thunk\Verbs\Event;

class PlayerCreatedStash extends Event
{
    use HasActivePlayer, HasGame, HasModifier, HasTeam;

    public int $x_index;

    public int $y_index;

    public function validate()
    {
        $game = $this->state(GameState::class);

        // Player has actions
        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $player_actions = $actions_state->modifier_data[$this->player_id]['actions'];

        $this->assert(
            $player_actions > 0,
            'Player does not have enough actions to create stash',
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

        $this->assert(
            ($player_space['stash_status']['amount'] ?? null) === 0,
            'Space already has a stash',
        );
    }

    public function apply(GameState $game)
    {
        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());

        $map_state->modifier_data = collect($map_state->modifier_data)->map(function ($space) use ($game) {
            if ($space['x-index'] === $this->x_index && $space['y-index'] === $this->y_index) {
                $space['stash_status']['player_owner_id'] = $this->player_id;
                $space['stash_status']['amount'] = 0;
                $space['history'][] = [
                    'round_number' => $game->currentChallenge()->round_number,
                    'emoji' => '🤫',
                    'message' => $this->state(PlayerState::class)->name.' hid a stash',
                ];
            }

            return $space;
        })->toArray();

        $player_skills = $game->modifiers()->firstWhere('class_key', FarmSkills::key())
            ->modifier_data[$this->player_id]['skills'];

        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $actions_state->modifier_data = collect($actions_state->modifier_data)
            ->map(function ($data, $player_id) use ($player_skills) {
                if ($player_id !== $this->player_id) {
                    return $data;
                }

                $thief_level = $player_skills['Thief'];
                $scout_level = $player_skills['Scout'];
                $action_cost = 4 - max($thief_level, $scout_level);

                $data['actions'] = $data['actions'] - $action_cost;

                return $data;
            })->toArray();
    }

    public function handle()
    {
        $this->game()->modifiers->each(fn ($modifier) => $modifier->updateModelWithStateData());
    }
}
