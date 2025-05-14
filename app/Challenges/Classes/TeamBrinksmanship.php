<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Traits\HasTeamPairs;
use App\Events\PlayerSubmittedNuclearStrike;
use App\Models\Player;
use App\States\GameState;
use App\States\TeamState;
use Illuminate\Support\Str;

class TeamBrinksmanship extends BaseChallengeClass
{
    use HasTeamPairs;

    const NAME = 'Brinksmanship';

    const DESCRIPTION = "Your team has nuclear codes, and you've been assigned an ally team: {ally_team}.
        You also have a field to input your ally's code.
        When you put in your ally's code, you'll have the option to either:
        Carpet Bomb (-10 points to all other teams), or Nuke Ally (-40 points to your ally).";

    const TYPE = 'team';

    public static function key(): string
    {
        return 'brinksmanship';
    }

    public function dataArrayForState(): array
    {
        return $this->challenge_state->game()
            ->teams()
            ->sortByDesc(fn ($team) => $team->score())
            ->mapWithKeys(fn ($t) => [$t->id => []])
            ->toArray();
    }

    private function generateNuclearCode(): string
    {
        // @todo decide what we want the nuclear code to be
        // see app/Rules/NuclearCode.php
        return strtoupper(Str::random(6));
    }

    public function frontendComponent(Player $player): array
    {
        $team_id = $player->team_id;

        if (! isset($this->challenge->challenge_data[$team_id])) {
            return $this->form()
                ->title(self::NAME)
                ->subtitle('Challenge data is being prepared...')
                ->build();
        }

        $team_data = $this->challenge->challenge_data[$team_id];
        $ally_team_id = $team_data['ally_team_id'];
        $ally_team = $player->game->teams->firstWhere('id', $ally_team_id);

        $description = strtr(self::DESCRIPTION, [
            '{ally_team}' => $ally_team->name,
        ]);

        // @todo use the when() function for the following:

        $form = $this->form()
            ->title(self::NAME)
            ->subtitle($description);

        $form->message('Your nuclear code: '.$team_data['code']);

        if ($team_data['has_launched']) {
            $strike_message = $team_data['strike_type'] === 'carpet_bomb'
                ? 'You carpet bombed all other teams!'
                : "You launched a nuclear strike against {$ally_team->name}!";

            $form->message($strike_message);
        } else {
            $form->input(
                property_name: 'target_code',
                label: 'Enter your ally\'s nuclear code',
                validation_rules: 'required|nuclear_code',
                validation_messages: [
                    'required' => 'You must enter a nuclear code',
                    'match_ally_code' => 'The nuclear code is incorrect. Please verify the code with your ally team.',
                ]
            );

            $form->buttonGroup()
                ->button(
                    label: 'Carpet Bomb (-10 to all other teams)',
                    action: 'carpetBomb',
                    properties_to_validate: ['target_code'],
                )
                ->button(
                    label: 'Nuke Ally (-40 to ally)',
                    action: 'nukeAlly',
                    properties_to_validate: ['target_code'],
                )
                ->endGroup();
        }

        return $form->build();
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

    public function onChallengeStarted(GameState $game_state)
    {
        $teams = collect($this->challenge_state->challenge_data);

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

        return $this->challenge_state->challenge_data = $paired_teams;
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
                    if ($other_team->id !== $team_id) {
                        $other_team->addToScoreHistory(
                            -10,
                            'Carpet bombed by '.TeamState::load($team_id)->name
                        );
                    }
                }
                // Apply -40 points to ally team
            } elseif ($strike_type === 'nuke_ally') {
                $ally_team = TeamState::load($ally_team_id);
                $team = TeamState::load($team_id);

                $ally_team->addToScoreHistory(
                    -40,
                    'Nuked by ally team '.$team->name
                );
            }
        }
    }
}
