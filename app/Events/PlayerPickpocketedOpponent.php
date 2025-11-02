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

class PlayerPickpocketedOpponent extends Event
{
    use HasActivePlayer, HasGame, HasModifier, HasTeam;

    public int $x_index;

    public int $y_index;

    public int $amount;

    public int $target_player_id;

    // for computing only
    public int $action_cost;

    public function validate()
    {
        $game = $this->state(GameState::class);
        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());
        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $skills_state = $game->modifiers()->firstWhere('class_key', FarmSkills::key());
        $player_data = $actions_state->modifier_data[$this->player_id];
        $player_skills = $skills_state->modifier_data[$this->player_id]['skills'];
        $target_player = PlayerState::load($this->target_player_id);

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

        // Opponent is on space
        $this->assert(
            in_array($this->target_player_id, $player_space['player_ids']),
            'Opponent is not on the same space',
        );

        // Opponent is on a different team
        $this->assert(
            $target_player->team_id !== $this->team_id,
            'Opponent is on the same team',
        );

        // Player has enough actions to pickpocket
        $player_actions = $actions_state->modifier_data[$this->player_id]['actions'];
        $this->action_cost = 4 - $player_skills['Thief'];
        $this->assert(
            $player_actions >= $this->action_cost,
            'Player does not have enough actions to pickpocket',
        );

        // opponent has that much grain
        $target_grain = $actions_state->modifier_data[$this->target_player_id]['grain'];
        $this->assert(
            $target_grain >= $this->amount,
            'Opponent does not have enough grain to pickpocket',
        );
    }

    public function apply(GameState $game)
    {
        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());

        $thief_state = $this->state(PlayerState::class);
        $target_state = $this->state(PlayerState::class, $this->target_player_id);

        $map_state->modifier_data = collect($map_state->modifier_data)->map(function ($space) use ($game, $thief_state, $target_state) {
            if ($space['x-index'] === $this->x_index && $space['y-index'] === $this->y_index) {
                $space['silo_status']['amount'] -= $this->amount;
                $space['history'][] = [
                    'round_number' => $game->currentChallenge()->round_number,
                    'emoji' => '🦹',
                    'message' => $thief_state->name.' pickpocketed '.$target_state->name.' and stole '.$this->amount.' grain',
                ];
            }

            return $space;
        })->toArray();

        $actions_state = $game->modifiers()->firstWhere('class_key', FarmActions::key());
        $actions_state->modifier_data = collect($actions_state->modifier_data)
            ->map(function ($data, $player_id) {
                $is_thief = $player_id === $this->player_id;
                $is_target = $player_id === $this->target_player_id;

                if (! $is_thief && ! $is_target) {
                    return $data;
                }

                if ($is_thief) {
                    $data['grain'] += $this->amount;
                    $data['actions'] -= $this->action_cost;
                }

                if ($is_target) {
                    $data['grain'] -= $this->amount;
                }

                return $data;
            })->toArray();
    }

    public function handle()
    {
        $this->game()->modifiers->each(fn ($modifier) => $modifier->updateModelWithStateData());
    }
}
