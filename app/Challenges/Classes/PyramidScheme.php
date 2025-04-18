<?php

namespace App\Challenges\Classes;

use App\States\GameState;
use App\States\PlayerState;
use App\States\TeamState;

class PyramidScheme extends BaseChallengeClass
{
    const NAME = 'Pyramid Scheme';

    const DESCRIPTION = "When a new player joins your team, gain 1 point. At the end of the challenge, the largest team's score will be set to zero.";

    public static function key(): string
    {
        return 'pyramid_scheme';
    }

    public function onPlayerJoinedTeam(
        PlayerState $player_state,
        TeamState $team_state,
        GameState $game_state,
        ?TeamState $previous_team = null,
    ) {
        if ($previous_team) {
            return;
        }

        $team_state->addToScoreHistory(1, $player_state->name.' joined team');
    }

    public function onChallengeEnded(
        GameState $game_state,
    ) {
        $first_place_team = $game_state->teams()->sortByDesc(fn ($t) => $t->score())->first();

        $first_place_team->addToScoreHistory(-$first_place_team->score(), 'Collapsed under the weight of its own success');
    }
}
