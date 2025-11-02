<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\GameState;
use App\States\PlayerState;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasTeam;
use App\Events\Traits\HasModifier;
use App\Modifiers\Classes\FarmMap;
use App\Modifiers\Classes\FarmSkills;
use App\Events\Traits\HasActivePlayer;
use App\Modifiers\Classes\FarmActions;

class PlayerBurnedField extends Event
{
    use HasActivePlayer, HasGame, HasModifier, HasTeam;

    public int $x_index;

    public int $y_index;

    public function validate()
    {
        $game = $this->state(GameState::class);

        // Player has actions
        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $player_skills = $game->modifiers()->firstWhere('class_key', FarmSkills::key());
        $thief_skill = $player_skills->modifier_data[$this->player_id]['skills']['Thief'];
        $brute_skill = $player_skills->modifier_data[$this->player_id]['skills']['Brute'];
        $action_cost = 4 - max($thief_skill, $brute_skill);

        $this->assert(
            $actions_state->modifier_data[$this->player_id]['actions'] >= $action_cost,
            'Player does not have enough actions to burn field',
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
    }

    public function apply(GameState $game)
    {
        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());

        $map_state->modifier_data = collect($map_state->modifier_data)
            ->map(function ($space) use ($game) {
                if ($space['x-index'] === $this->x_index && $space['y-index'] === $this->y_index) {
                    $space['field_status']['stage'] = null;
                    $space['field_status']['level'] = null;
                    $space['field_status']['owner_team_id'] = null;
                    $space['history'][] = [
                        'round_number' => $game->currentChallenge()->round_number,
                        'emoji' => '🔥',
                        'message' => $this->state(PlayerState::class)->name.' burned a field',
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
                $brute_level = $player_skills['Brute'];
                $action_cost = 4 - max($thief_level, $brute_level);

                $data['actions'] = $data['actions'] - $action_cost;

                return $data;
            })->toArray();
    }

    public function handle()
    {
        $this->game()->modifiers->each(fn ($modifier) => $modifier->updateModelWithStateData());
        $this->team()->updateModelWithStateData();
    }
}
