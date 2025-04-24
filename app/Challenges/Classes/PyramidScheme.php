<?php

namespace App\Challenges\Classes;

use App\Models\Player;
use App\States\GameState;
use App\States\TeamState;
use App\States\PlayerState;

class PyramidScheme extends BaseChallengeClass
{
    public static function key(): string
    {
        return 'pyramid_scheme';
    }

    public function frontendComponent(): array
    {
        return $this->form()
            ->title('Pyramid Scheme')
            ->subtitle('When a new player joins your team, gain 1 point. At the end of the challenge, the largest team\'s score will be set to zero. You may change teams once during this challenge.')
            // @todo this needs to take into account the player who is seeing this component
            // ->teamSwap($this->game->teams->fil)
            ->build();
    }

    public function playerCanSwapTeams(?Player $player = null, ?PlayerState $player_state = null): bool
    {
        if ($player) {
            return !in_array($player_state->id, $this->challenge->challenge_data['swapper_ids']);
        }

        if ($player_state) {
            return !in_array($player_state->id, $this->challenge_state->challenge_data['swapper_ids']);
        }

        return false;
    }

    public function onPlayerJoinedTeam(
        PlayerState $player_state,
        TeamState $team_state,
        GameState $game_state,
        ?TeamState $previous_team = null,
    ) {
        if ($previous_team) {
            $this->challenge_state->challenge_data['swapper_ids'][] = $player_state->id;
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
