<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasTeam;
use App\Modifiers\Classes\FarmActions;
use App\Modifiers\Classes\FarmMap;
use App\Modifiers\Classes\FarmSkills;
use App\States\GameState;
use App\States\PlayerState;
use App\States\TeamState;
use Thunk\Verbs\Event;

class PlayerSeizedFarmProperty extends Event
{
    use HasGame, HasModifier, HasPlayer, HasTeam;

    public int $previous_owner_team_id;

    public int $x_index;

    public int $y_index;

    public string $property_type;

    public ?int $grain_transferred = null;

    // for computation only

    protected int $action_cost;

    public function validate()
    {
        $game = $this->state(GameState::class);

        $player_skills = $game->modifiers()->firstWhere('class_key', FarmSkills::key())->modifier_data[$this->player_id]['skills'];

        $player_actions = $game->modifiers()->firstWhere('class_key', FarmActions::key())->modifier_data[$this->player_id]['actions'];
        $this->action_cost = 4 - $player_skills['Brute'];

        $this->assert(
            $player_actions >= $this->action_cost,
            'Player does not have enough actions to seize property',
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

        // Property type is valid ('silo' or 'field')
        $this->assert(
            in_array($this->property_type, ['silo', 'field']),
            'Property type must be either silo or field',
        );

        // Property is owned by another team
        $property_owner = $player_space[$this->property_type.'_status']['owner_team_id'] ?? null;

        $this->assert(
            $property_owner !== null,
            'Property does not exist on this space',
        );

        $this->assert(
            $property_owner !== $this->team_id,
            'Property is already owned by player\'s team',
        );

        $this->assert(
            $property_owner === $this->previous_owner_team_id,
            'Previous owner team ID does not match actual property owner',
        );
    }

    public function apply(GameState $game)
    {
        $previous_owner_state = TeamState::load($this->previous_owner_team_id);
        $new_owner_state = TeamState::load($this->team_id);

        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());
        $map_state->modifier_data = collect($map_state->modifier_data)->map(function ($space) use ($game, $previous_owner_state) {
            if ($space['x-index'] === $this->x_index && $space['y-index'] === $this->y_index) {
                $space[$this->property_type.'_status']['owner_team_id'] = $this->team_id;
                $space['history'][] = [
                    'round_number' => $game->currentChallenge()->round_number,
                    'emoji' => '⚔️',
                    'message' => $this->state(PlayerState::class)->name.' seized '.$this->property_type.' from '.$previous_owner_state->name,
                ];
            }

            return $space;
        })->toArray();

        if (! isset($this->action_cost)) {
            $player_skills = $game->modifiers()->firstWhere('class_key', FarmSkills::key())->modifier_data[$this->player_id]['skills'];
            $this->action_cost = 4 - $player_skills['Brute'];
        }

        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $actions_state->modifier_data = collect($actions_state->modifier_data)
            ->map(function ($data, $player_id) {
                if ($player_id !== $this->player_id) {
                    return $data;
                }

                $data['actions'] -= $this->action_cost;

                return $data;
            })->toArray();

        if (! $this->grain_transferred) {
            return;
        }

        $previous_owner_state->addToScoreHistory('😧', -$this->grain_transferred, $new_owner_state->name.' seized '.$this->property_type.' and took '.$this->grain_transferred.' grain.');
        $new_owner_state->addToScoreHistory('😈', $this->grain_transferred, 'Seized '.$this->property_type.' from '.$previous_owner_state->name.' and gained '.$this->grain_transferred.' grain.');
    }

    public function handle()
    {
        $this->game()->modifiers->each(fn ($modifier) => $modifier->updateModelWithStateData());
        $this->game()->teams->each(fn ($team) => $team->updateModelWithStateData());
    }
}
