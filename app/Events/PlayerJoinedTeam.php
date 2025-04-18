<?php

namespace App\Events;

use App\Models\Player;
use App\States\GameState;
use App\States\PlayerState;
use App\States\TeamState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class PlayerJoinedTeam extends Event
{
    #[StateId(PlayerState::class)]
    public int $player_id;

    #[StateId(TeamState::class)]
    public int $team_id;

    #[StateId(GameState::class)]
    public int $game_id;

    public ?int $previous_team_id = null;

    public function validate()
    {
        $this->assert(
            $this->game_id === $this->state(PlayerState::class)->game_id,
            'Player is not in the game',
        );

        $this->assert(
            $this->state(GameState::class)->team_ids->contains($this->team_id),
            'Team is not in the game',
        );

        $this->assert(
            ! $this->state(TeamState::class)->player_ids->contains($this->player_id),
            'Player is already in a team',
        );

        if (isset($this->previous_team_id)) {
            $this->assert(
                $this->state(GameState::class)->team_ids->contains($this->previous_team_id),
                'Previous team is not in the game',
            );

            $this->assert(
                TeamState::load($this->previous_team_id)->player_ids->contains($this->player_id),
                'Player was not in the previous team',
            );
        }
    }

    public function applyToPlayer(PlayerState $player)
    {
        $player->team_id = $this->team_id;

        if (isset($this->previous_team_id)) {
            $player->last_switched_team_at = now();
        }
    }

    public function applyToTeam(TeamState $team)
    {
        $team->player_ids->push($this->player_id);

        if (isset($this->previous_team_id)) {
            $state = TeamState::load($this->previous_team_id);
            $state->player_ids = $state->player_ids->reject(fn (int $player_id) => $player_id === $this->player_id);
        }
    }

    public function handle()
    {
        $player = Player::find($this->player_id);

        $player->team_id = $this->team_id;

        if (isset($this->previous_team_id)) {
            $player->last_switched_team_at = $this->state(PlayerState::class)->last_switched_team_at;
        }

        $player->save();
    }
}
