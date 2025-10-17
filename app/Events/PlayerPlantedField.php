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
use Thunk\Verbs\Event;

class PlayerPlantedField extends Event
{
    use HasActivePlayer, HasGame, HasModifier, HasTeam;

    public int $x_index;

    public int $y_index;

    public int $level;

    public function validate()
    {
        $game = $this->state(GameState::class);

        // Player has actions
        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $player_actions = $actions_state->modifier_data[$this->player_id]['actions'];

        $this->assert(
            $player_actions > 0,
            'Player does not have enough actions to plant field',
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

        // $this->level matches player's farmer level
        $skills_state = $game->modifiers()->firstWhere('class_key', \App\Modifiers\Classes\FarmSkills::key());
        $player_farmer_level = $skills_state->modifier_data[$this->player_id]['skills']['Farmer'];

        $this->assert(
            $player_farmer_level === $this->level,
            'Field level does not match player\'s Farmer skill level',
        );

        // Space had no field
        $this->assert(
            ($player_space['field_status']['level'] ?? null) === null,
            'Space already has a field',
        );

        // Space was not swamp, mountain, volcano, or ash heap
        $invalid_types = ['swamp', 'mountain', 'volcano', 'ash_heap'];
        $this->assert(
            !in_array($player_space['type'], $invalid_types),
            'Cannot plant field on '.$player_space['type'].' terrain',
        );
    }

    public function apply(GameState $game)
    {
        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());

        $map_state->modifier_data = collect($map_state->modifier_data)->map(function ($space) use ($game) {
            if ($space['x-index'] === $this->x_index && $space['y-index'] === $this->y_index) {
                $space['field_status']['level'] = $this->level;
                $space['field_status']['owner_team_id'] = $this->team_id;
                $space['field_status']['stage'] = 'seedlings';
                $space['history'][] = [
                    'round_number' => $game->currentChallenge()->round_number,
                    'emoji' => '🌱',
                    'message' => $this->state(PlayerState::class)->name.' planted a level '.$this->level.' field',
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

                $data['actions'] = $data['actions'] - 1;

                return $data;
            })->toArray();
    }

    public function handle()
    {
        $this->game()->modifiers->each(fn ($modifier) => $modifier->updateModelWithStateData());
    }
}
