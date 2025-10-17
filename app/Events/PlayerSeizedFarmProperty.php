<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasTeam;
use App\Modifiers\Classes\FarmMap;
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

    public function validate()
    {
        $game = $this->state(GameState::class);

        // Player has actions
        $actions_state = $game->modifiers()->firstWhere('class_key', \App\Modifiers\Classes\FarmActions::key());
        $player_actions = $actions_state->modifier_data[$this->player_id]['actions'];

        $this->assert(
            $player_actions > 0,
            'Player does not have enough actions',
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

        // Player (and teammates on space) have enough brute strength to seize property
        $skills_state = $game->modifiers()->firstWhere('class_key', \App\Modifiers\Classes\FarmSkills::key());
        $players_on_space = collect($player_space['player_ids'])
            ->map(fn ($pid) => \App\States\PlayerState::load($pid));

        $property_level = $player_space[$this->property_type.'_status']['level'];

        // Calculate attack power (player's team members on space)
        $attackers = $players_on_space->filter(fn ($p) => $p->team_id === $this->team_id);
        $attack_power = $attackers->sum(fn ($p) => $skills_state->modifier_data[$p->id]['skills']['Brute']);

        // Calculate defense power (property level + defenders' brute strength)
        $defenders = $players_on_space->filter(fn ($p) => $p->team_id === $property_owner);
        $defense_power = $property_level - 1 + $defenders->sum(fn ($p) => $skills_state->modifier_data[$p->id]['skills']['Brute']);

        $this->assert(
            $attack_power > $defense_power,
            'Not enough brute strength to seize property (Attack: '.$attack_power.', Defense: '.$defense_power.')',
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
