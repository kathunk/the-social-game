<?php

use App\Models\Team;
use App\States\TeamState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

function incrementScore(Team $team, int $points)
{
    ScoreIncremented::fire(
        team_id: $team->id,
        points: $points,
    );
}

class ScoreIncremented extends Event
{
    #[StateId(TeamState::class)]
    public ?int $team_id = null;

    public int $points;

    public function validate()
    {
        return true;
    }

    public function applyToTeam(TeamState $team)
    {
        $team->addToScoreHistory($this->points, 'score_incremented');
    }

    public function handle(TeamState $state)
    {
        $team = Team::find($this->team_id);
        $team->score = $state->score();
        $team->save();
    }
}
