<?php

namespace App\Challenges\Classes;

use App\Models\Player;
use App\States\GameState;
use App\States\PlayerState;
use App\States\TeamState;

class ExampleTeamChallenge extends BaseChallengeClass
{
    const NAME = 'Example Challenge';

    const DESCRIPTION = 'Example Challenge description';

    const TYPE = 'team'; // team or individual

    public static function key(): string
    {
        return 'example_team_challenge';
    }

    public function frontendComponent(Player $player): array
    {
        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->image('https://upload.wikimedia.org/wikipedia/commons/thumb/e/e7/Great_Pyramid_of_Giza_-_Pyramid_of_Khufu.jpg/960px-Great_Pyramid_of_Giza_-_Pyramid_of_Khufu.jpg', 'A pyramid')
            ->input(
                property_name: 'pick_a_number',
                label: 'Pick a number 1-10',
                description: 'If you pick a number that no other player chose, gain 5 points. If you pick the most common number, lose 5 points.',
                validation_rules: 'required|numeric|min:1|max:10',
                validation_messages: [
                    'required' => 'Pick a number',
                    'numeric' => 'That\'s not a number...',
                    'min' => 'The number cannot be less than 1',
                    'max' => 'The number cannot be more than 10',
                ]
            )
            ->buttonGroup()
            ->button('Submit Number', 'submitNumber')
            ->endGroup()
            ->divider()
            ->select(
                property_name: 'new_team_id',
                options: $this->challenge->game->teams->mapWithKeys(fn ($t) => [$t->id => $t->name])->toArray(),
                label: 'Select a new team',
                description: 'You will join this new team immediately.',
                placeholder: 'Select a team...',
                validation_rules: 'in:'.$this->challenge->game->teams->pluck('id')->join(','),
                validation_messages: [
                    'in' => 'Must select a valid team',
                ],
            )
            ->build();
    }

    public function submitNumber($params)
    {
        dd(implode(' ', $params));
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
