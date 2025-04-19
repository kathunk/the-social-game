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

    public function frontendComponent(): array
    {
        return $this->form()
            ->title('Pyramid Scheme')
            ->subtitle('When a new player joins your team, gain 1 point. At the end of the challenge, the largest team\'s score will be set to zero.')
            ->image('https://upload.wikimedia.org/wikipedia/commons/thumb/e/e7/Great_Pyramid_of_Giza_-_Pyramid_of_Khufu.jpg/960px-Great_Pyramid_of_Giza_-_Pyramid_of_Khufu.jpg', 'A pyramid')
            ->divider()
            ->buttonGroup()
                ->button('Add Player', 'add_player')
                ->button('Remove Player', 'remove_player')
            ->endGroup()
            // ->table([
            //     'Team' => 'Score',
            //     'Team 1' => '100',
            //     'Team 2' => '80',
            //     'Team 3' => '60',
            // ])
            ->build();
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
