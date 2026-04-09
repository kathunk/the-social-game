<?php

namespace App\Events\Farm;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasTeam;
use App\Modifiers\Classes\Farm\FarmActions;
use App\Modifiers\Classes\Farm\FarmMap;
use App\Modifiers\Classes\Farm\FarmSkills;
use App\States\GameState;
use App\States\PlayerState;
use App\States\TeamState;
use Thunk\Verbs\Event;

class PlayerWithdrewFromSilo extends Event
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

        // Player has enough capacity
        $player_grain = $player_data['grain'];
        $player_capacity = $player_data['grain_capacity'];
        $this->assert(
            $player_grain + $this->amount <= $player_capacity,
            'Player does not have enough capacity (has '.$player_grain.'/'.$player_capacity.', trying to withdraw '.$this->amount.')',
        );

        // Silo is in space
        $silo_level = $player_space['silo_status']['level'] ?? null;
        $this->assert(
            $silo_level !== null && $silo_level > 0,
            'There is no silo in this space',
        );

        // Silo has enough grain to withdraw
        $silo_amount = $player_space['silo_status']['amount'];
        $this->assert(
            $silo_amount >= $this->amount,
            'Silo does not have enough grain (has '.$silo_amount.', trying to withdraw '.$this->amount.')',
        );

        $silo_belongs_to_player = $player_space['silo_status']['owner_team_id'] === $this->team_id;

        if ($silo_belongs_to_player) {
            return;
        }

        $player_thief_level = $game->modifiers()->firstWhere('class_key', FarmSkills::key())->modifier_data[$this->player_id]['skills']['Thief'];

        $this->assert(
            $player_thief_level >= 1,
            'Player must be a thief the withdraw grain from a silo they do not own',
        );
    }

    public function apply(GameState $game)
    {
        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());
        $player_state = $this->state(PlayerState::class);
        $team_state = $this->state(TeamState::class);
        $silo_data = collect($map_state->modifier_data)
            ->filter(fn ($space) => $space['x-index'] === $this->x_index && $space['y-index'] === $this->y_index)
            ->first()['silo_status'];

        $map_state->modifier_data = collect($map_state->modifier_data)->map(function ($space) use ($game) {
            if ($space['x-index'] === $this->x_index && $space['y-index'] === $this->y_index) {
                $space['silo_status']['amount'] -= $this->amount;
                $space['history'][] = [
                    'round_number' => $game->currentChallenge()->round_number,
                    'emoji' => '📉',
                    'message' => $this->state(PlayerState::class)->name.' withdrew '.$this->amount.' grain from silo',
                ];
            }

            return $space;
        })->toArray();

        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $actions_state->modifier_data = collect($actions_state->modifier_data)
            ->map(function ($data, $player_id) use ($game, $silo_data) {
                if ($player_id !== $this->player_id) {
                    return $data;
                }

                $player_skills = $game->modifiers()->firstWhere('class_key', FarmSkills::key())
                    ->modifier_data[$this->player_id]['skills'];
                $silo_belongs_to_player = $silo_data['owner_team_id'] === $this->team_id;

                $thief_cost = 4 - $player_skills['Thief'];
                $action_cost = $silo_belongs_to_player ? 0 : $thief_cost;

                $data['actions'] -= $action_cost;
                $data['grain'] += $this->amount;

                return $data;
            })->toArray();

        // if ($silo_data['owner_team_id'] === $this->team_id) {
        //     return;
        // }

        // $silo_owner_team = TeamState::load($silo_data['owner_team_id']);
        // $silo_owner_team->addToScoreHistory(
        //     icon: '😈',
        //     points: -$this->amount,
        //     description: 'Some scoundrel withdrew '.$this->amount.' grain from your silo.',
        // );
        // $team_state->addToScoreHistory(
        //     icon: '😈',
        //     points: $this->amount,
        //     description: 'A scoundrel on your team withdrew '.$this->amount.' grain from a silo you do not own.',
        // );
    }

    public function handle()
    {
        $this->game()->modifiers->each(fn ($modifier) => $modifier->updateModelWithStateData());
        $this->game()->teams->each(fn ($team) => $team->updateModelWithStateData());
    }
}
