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
        // player has actions
        // player is in correct space
        // property is owned by another team
        // property type is valid
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
