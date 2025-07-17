<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Traits\HasTeamPairs;
use App\Events\PlayerSubmittedNuclearStrike;
use App\Models\Player;
use App\States\GameState;
use App\States\TeamState;

class TeamBrinksmanship extends BaseChallengeClass
{
    use HasTeamPairs;

    const NAME = 'Brinksmanship';

    const DESCRIPTION = "{your_team} has a secret code that is only useful to your ally team: {ally_team}. They have a code that you can use.
        When you put in your ally's code, you'll have the option to either:
        Give -10 points to all teams other than you and your ally, or betray your ally and give them -50 points.";

    const TYPE = 'team';

    public static function key(): string
    {
        return 'brinksmanship';
    }

    public function isInvalidForTemplate(array $challenges, array $modifiers, string $type, array $team_names)
    {
        if (count($team_names) % 2 !== 0) {
            return 'Brinksmanship requires an even number of teams.';
        }

        return false;
    }

    public function dataArrayForState(): array
    {
        $teams = $this->challenge->game->teams;

        $paired_teams = $this->pair($teams);

        $paired_teams = $paired_teams->mapWithKeys(function ($team_id, $ally_team_id) {
            return [
                $team_id => [
                    'code' => $this->generateNuclearCode(),
                    'ally_team_id' => $ally_team_id,
                    'has_launched' => false,
                    'strike_type' => null,
                ],
            ];
        })->toArray();

        return $paired_teams;
    }

    private function generateNuclearCode(): string
    {
        return random_int(100000, 999999);
    }

    public function frontendComponent(Player $player): array
    {
        $team_data = $this->challenge->challenge_data[$player->team_id];

        $ally_team = $player->game->teams->firstWhere('id', $team_data['ally_team_id']);

        $description = strtr(self::DESCRIPTION, [
            '{your_team}' => $player->team->name,
            '{ally_team}' => $ally_team->name,
        ]);

        return $this->form()
            ->title(self::NAME)
            ->subtitle($description)
            ->subtitle('Your nuclear code: '.$team_data['code'])
            ->when($team_data['has_launched'], function ($form) use ($team_data, $ally_team) {
                $strike_message = $team_data['strike_type'] === 'carpet_bomb'
                    ? 'You gave -10 points to all other teams!'
                    : "You betrayed {$ally_team->name} and gave them -50 points!";

                return $form->subtitle($strike_message);
            })
            ->when(! $team_data['has_launched'], fn ($form) => $form->input(
                property_name: 'target_code',
                label: 'Enter your ally\'s code',
                validation_rules: 'required|nuclear_code',
                validation_messages: [
                    'required' => 'You must enter a code',
                    'nuclear_code' => 'The code is incorrect. Please verify the code with your ally team.',
                ]
            )
                ->buttonGroup()
                ->button(
                    label: 'Give -10 points to all other teams',
                    action: 'carpetBomb',
                    properties_to_validate: ['target_code'],
                )
                ->button(
                    label: 'Betray your ally and give them -50 points',
                    action: 'nukeAlly',
                    properties_to_validate: ['target_code'],
                )
                ->endGroup()
            )
            ->build();
    }

    public function carpetBomb(Player $player, array $params)
    {
        PlayerSubmittedNuclearStrike::fire(
            player_id: $player->id,
            game_id: $player->game_id,
            challenge_id: $this->challenge->id,
            team_id: $player->team_id,
            strike_type: 'carpet_bomb',
            target_code: $params['target_code'],
        );
    }

    public function nukeAlly(Player $player, array $params)
    {
        PlayerSubmittedNuclearStrike::fire(
            player_id: $player->id,
            game_id: $player->game_id,
            challenge_id: $this->challenge->id,
            team_id: $player->team_id,
            strike_type: 'nuke_ally',
            target_code: $params['target_code'],
        );
    }

    public function onChallengeEnded(GameState $game_state)
    {
        $teams = $game_state->teams();
        $teams_data = $this->challenge_state->challenge_data;

        foreach ($teams_data as $team_id => $team_data) {
            if (! $team_data['has_launched']) {
                continue;
            }

            $strike_type = $team_data['strike_type'];
            $ally_team_id = $team_data['ally_team_id'];

            // Apply -10 points to all other teams
            if ($strike_type === 'carpet_bomb') {
                foreach ($teams as $other_team) {
                    if ($other_team->id !== $team_id && $other_team->id !== $ally_team_id) {
                        $other_team->addToScoreHistory(
                            -10,
                            '👊 Attacked by '.TeamState::load($team_id)->name
                        );
                    }
                }
                // Apply -50 points to ally team
            } elseif ($strike_type === 'nuke_ally') {
                $ally_team = TeamState::load($ally_team_id);
                $team = TeamState::load($team_id);

                $ally_team->addToScoreHistory(
                    -50,
                    '🗡️ Betrayed by '.$team->name
                );
            }
        }
    }
}
