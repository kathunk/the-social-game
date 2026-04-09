<?php

namespace App\Challenges\Classes\Laracon2025;

use App\Challenges\Classes\BaseChallengeClass;
use App\Challenges\Support\Laracon2025\HasTeamPairs;
use App\Events\Laracon2025\PlayerSubmittedPlayDirty;
use App\Models\Player;
use App\Models\Team;
use App\States\GameState;
use App\States\TeamState;

class TeamPrisonersDilemma extends BaseChallengeClass
{
    use HasTeamPairs;

    const NAME = "Prisoner's Dilemma";

    const DESCRIPTION = '{player_team} (your team) is in a showdown with {paired_team}.
        If 50% of your team votes to play dirty, you will.
        If both teams play dirty, each team will get -20 points.
        If neither plays dirty, each team will get +20 points.
        If {player_team} plays dirty and {paired_team} does not, {player_team} will get +50 points.';

    const TYPE = 'team';

    public static function key(): string
    {
        return 'team_prisoners_dilemma';
    }

    public function isInvalidForTemplate(array $challenges, array $modifiers, string $type, array $team_names)
    {
        if (count($team_names) % 2 !== 0) {
            return "Prisoner's Dilemma requires an even number of teams.";
        }

        return false;
    }

    public function dataArrayForState(): array
    {
        $teams = $this->challenge->game->teams;

        $paired_teams = $this->pair($teams)->toArray();

        return [
            'team_voters' => $teams->mapWithKeys(fn ($team) => [$team->id => []])->toArray(),
            'team_pairs' => $paired_teams,
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $form = $this->form()->title(self::NAME);

        $has_voted = collect($this->challenge->challenge_data['team_voters'][$player->team_id])->contains($player->id);

        if (! $has_voted) {
            $paired_team_id = $this->challenge->challenge_data['team_pairs'][$player->team_id];

            $description = strtr(self::DESCRIPTION, [
                '{paired_team}' => Team::find($paired_team_id)->name,
                '{player_team}' => $player->team->name,
            ]);

            $form->subtitle($description)
                ->buttonGroup()
                ->button('Play Dirty', 'playDirty')
                ->endGroup();
        } else {
            $form->subtitle('😈 You played dirty.');
        }

        return $form->build();
    }

    public function playDirty(Player $player, array $params): void
    {
        PlayerSubmittedPlayDirty::fire(
            player_id: $player->id,
            game_id: $player->game_id,
            challenge_id: $this->challenge->id,
            team_id: $player->team_id,
        );
    }

    public function onChallengeEnded(GameState $game_state)
    {
        $teams = $game_state->teams();

        $played_dirty = $teams->map(function ($team) {
            $member_count = $team->player_ids->count();

            if ($member_count === 0) {
                return;
            }

            $team_voters = collect($this->challenge_state->challenge_data['team_voters'][$team->id]);

            if (count($team_voters) === 0) {
                return;
            }

            // If any of the players who voted are no longer on the team, remove them from team_voters
            $team_voters = $team_voters->filter(fn ($voter) => $team->player_ids->contains($voter));

            if (count($team_voters) === 0) {
                return;
            }

            // If 50% of your team votes to play dirty, you will.
            if (count($team_voters) >= ($member_count / 2)) {
                return $team->id;
            }
        });

        $team_pairs = $this->challenge_state->challenge_data['team_pairs'];

        foreach ($team_pairs as $team_id => $paired_team_id) {
            $team = TeamState::load($team_id);

            $containsTeamId = $played_dirty->contains($team_id);
            $containsPairedTeamId = $played_dirty->contains($paired_team_id);

            $team_name = $team->name;
            $paired_team_name = $teams->firstWhere('id', $paired_team_id)->name;

            if (
                $containsTeamId
                && $containsPairedTeamId
            ) {
                $team->addToScoreHistory(
                    icon: '😩',
                    points: -20,
                    description: $team_name.' and '.$paired_team_name.' both played dirty',
                );
            } elseif (
                $containsTeamId
                && ! $containsPairedTeamId
            ) {
                $team->addToScoreHistory(
                    icon: '😈',
                    points: 50,
                    description: $team_name.' played dirty and '.$paired_team_name.' did not',
                );
            } elseif (
                ! $containsTeamId
                && ! $containsPairedTeamId
            ) {
                $team->addToScoreHistory(
                    icon: '😇',
                    points: 20,
                    description: 'Neither '.$team_name.' nor '.$paired_team_name.' played dirty',
                );
            } elseif (
                ! $containsTeamId
                && $containsPairedTeamId
            ) {
                $team->addToScoreHistory(
                    icon: '😩',
                    points: 0,
                    description: $team_name.' was nice, but '.$paired_team_name.' played dirty',
                );
            }
        }
    }
}
