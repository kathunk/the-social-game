<?php

namespace App\Events;

use App\Events\Traits\HasActiveGame;
use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasTeam;
use App\Models\Game;
use App\Models\Player;
use App\States\GameState;
use App\States\PlayerState;
use App\States\TeamState;
use Thunk\Verbs\Event;

class PlayerJoinedTeam extends Event
{
    use HasActiveGame, HasActivePlayer, HasTeam;

    public ?int $previous_team_id = null;

    public function validate()
    {
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

    public function applyToGame(GameState $game)
    {
        $game->currentChallenge()->handler()->onPlayerJoinedTeam(
            player_state: $this->state(PlayerState::class),
            team_state: $this->state(TeamState::class),
            game_state: $this->state(GameState::class),
            previous_team: $this->previous_team_id ? TeamState::load($this->previous_team_id) : null,
        );
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

        $game = Game::find($this->game_id);
        
        $game->teams->each(fn ($t) => $t->update(['score' => $t->state()->score()]));

        $game->currentChallenge()->updateModelWithStateData();
    }
}
