<?php

namespace App\Events;

use App\Models\Team;
use App\Models\Player;
use Thunk\Verbs\Event;
use App\States\GameState;
use App\States\TeamState;
use App\States\PlayerState;
use App\Models\ScoreHistoryEntry;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

class PlayerResigned extends Event
{
    #[StateId(PlayerState::class)]
    public int $player_id;

    #[StateId(TeamState::class)]
    public int $team_id;

    #[StateId(GameState::class)]
    public int $game_id;

    public int $points;

    public function validate()
    {
        $this->assert(
            $this->state(GameState::class)->player_ids->contains($this->player_id),
            'Player is not in the game',
        );

        $this->assert(
            $this->state(GameState::class)->resigned_player_ids->doesntContain($this->player_id),
            'Player has already resigned',
        );

        $this->assert(
            $this->state(GameState::class)->team_ids->contains($this->team_id),
            'Team is not in the game',
        );

        $this->assert(
            $this->state(TeamState::class)->player_ids->contains($this->player_id),
            'Player is not in the team',
        );

        $this->assert(
            $this->state(GameState::class)->status === 'active',
            'Game is not active',
        );

        $this->assert(
            $this->points === 3 || $this->points === -3,
            'Points must be 3 or -3',
        );
    }

    public function applyToGame(GameState $game)
    {
        $game->resigned_player_ids->push($this->player_id);
        $game->player_ids = $game->player_ids->reject(fn (int $player_id) => $player_id === $this->player_id);
    }

    public function applyToTeam(TeamState $team)
    {
        $team->player_ids = $team->player_ids->reject(fn (int $player_id) => $player_id === $this->player_id);
        $team->addToScoreHistory($this->points, $this->state(PlayerState::class)->name .' resigned');
    }

    public function handle()
    {
        $player = Player::find($this->player_id);
        $player->team_id = null;
        $player->status = 'resigned';
        $player->save();

        ScoreHistoryEntry::create([
            'team_id' => $this->team_id,
            'game_id' => $this->game_id,
            'points' => $this->points,
            'description' => $this->state(PlayerState::class)->name .' resigned',
        ]);

        $team = Team::find($this->team_id);
        $team->score = $this->state(TeamState::class)->score();
        $team->save();
    }
}
