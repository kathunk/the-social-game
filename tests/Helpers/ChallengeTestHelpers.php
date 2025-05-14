<?php

use App\Models\Player;
use App\Models\Team;
use App\States\PlayerState;
use App\States\TeamState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

function incrementScore(int $points, ?Team $team = null, ?Player $player = null, ?bool $is_hidden = false)
{
    if ($team) {
        TeamScoreIncremented::fire(
            team_id: $team->id,
            points: $points,
            is_hidden: $is_hidden,
        );
    } else {
        PlayerScoreIncremented::fire(
            player_id: $player->id,
            points: $points,
            is_hidden: $is_hidden,
        );
    }
}

class TeamScoreIncremented extends Event
{
    #[StateId(TeamState::class)]
    public ?int $team_id = null;

    public int $points;

    public bool $is_hidden;

    public function applyToTeam(TeamState $team)
    {
        $team->addToScoreHistory($this->points, 'score_incremented', $this->is_hidden);
    }

    public function handle(TeamState $state)
    {
        $team = Team::find($this->team_id);
        $team->score = $state->score();
        $team->save();
    }
}

class PlayerScoreIncremented extends Event
{
    #[StateId(PlayerState::class)]
    public ?int $player_id = null;

    public int $points;

    public bool $is_hidden;

    public function applyToPlayer(PlayerState $player)
    {
        $player->addToScoreHistory($this->points, 'score_incremented', $this->is_hidden);
    }

    public function handle(PlayerState $state)
    {
        $player = Player::find($this->player_id);
        $player->score = $state->score();
        $player->hidden_score = $state->score(include_hidden: true);
        $player->save();
    }
}
