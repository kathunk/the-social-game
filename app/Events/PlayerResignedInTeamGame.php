<?php

namespace App\Events;

use App\Events\Traits\HasActiveGame;
use App\Events\Traits\HasPlayerOnTeam;
use App\Models\Player;
use App\Models\Team;
use App\States\GameState;
use App\States\PlayerState;
use App\States\TeamState;
use Thunk\Verbs\Event;

class PlayerResignedInTeamGame extends Event
{
    use HasActiveGame, HasPlayerOnTeam;

    public int $points;

    public function validate()
    {
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
        $team->addToScoreHistory($this->points, '👻 '.$this->state(PlayerState::class)->name.' resigned');
    }

    public function handle()
    {
        $player = Player::find($this->player_id);
        $player->team_id = null;
        $player->status = 'resigned';
        $player->save();

        $team = Team::find($this->team_id);
        $team->score = $this->state(TeamState::class)->score();
        $team->save();
    }
}
