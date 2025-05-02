<?php

namespace App\Challenges\Classes;

use App\Models\Team;
use App\Models\Player;
use App\States\GameState;
use App\States\TeamState;
use App\States\PlayerState;
use Illuminate\Support\Collection;
use App\Events\PlayerSubmittedPlayDirty;
use App\Challenges\Support\Traits\HasTeamSwaps;
use App\Challenges\Support\Interfaces\SupportsTeamSwaps;

class PrisonersDilemma extends BaseChallengeClass implements SupportsTeamSwaps
{
    use HasTeamSwaps;

    const NAME = "Prisoner's Dilemma";

    const DESCRIPTION = "Your team is in a showdown with {paired_team}.
        Below is a button to play dirty.
        If 50% of your team votes to play dirty, you will.
        If both teams play dirty, they will each get -20 points.
        If neither plays dirty, they will each get +20 points.
        If you play dirty and they do not, you will get +50 points.";

    const TYPE = 'team';

    public static function key(): string
    {
        return 'prisoners_dilemma_challenge';
    }

    public function dataArrayForState(): array
    {
        $teams = $this->challenge_state->game()->teams()->sortByDesc('score')->pluck('id');

        $paired_teams = $this->pair($teams);

        return [
            'team_voters' => $this->challenge_state->game()->teams()->mapWithKeys(fn ($t) => [$t->id => []])->toArray(),
            'swapper_ids' => [],
            'team_pairs' => $paired_teams,
        ];
    }

    public function pair(Collection $teams): array
    {
        // @todo in the future: account for scenarios where there is an odd number of teams
        $paired_teams = collect();

        while ($teams->count() >= 2) {
            [$team1, $team2] = $teams->splice(0, 2);

            $paired_teams
                ->put($team1, $team2)
                ->put($team2, $team1);
        }

        return $paired_teams->toArray();
    }

    public function frontendComponent(Player $player): array
    {
        if (! $player->team) {
            return $this->form()
                ->title(self::NAME)
                ->subtitle('You are not on a team.')
                ->build();
        }

        $form = $this->form()->title(self::NAME);

        $canVoteForCurrentTeam = collect($this->challenge->challenge_data['team_voters'][$player->team_id])->doesntContain($player->id);

        if ($canVoteForCurrentTeam) {
            $paired_team_id = $this->challenge->challenge_data['team_pairs'][$player->team_id];

            $description = strtr(self::DESCRIPTION, [
                '{paired_team}' => Team::find($paired_team_id)->name,
            ]);

            $form->subtitle($description)
                ->buttonGroup()
                ->button('Play Dirty', 'submit')
                ->endGroup();
        } else {
            $form->subtitle('You have chosen to play dirty.');
        }

        if ($this->playerCanSwapTeams($player)) {
            $form
                ->divider()
                ->teamSwap(
                    teams: $this->availableTeams($player),
                    required: false
                );
        }

        return $form->build();
    }

    public function availableTeams(Player $player): Collection
    {
        return $this->playerCanSwapTeams($player)
            ? $player->game->teams->filter(fn ($t) => $t->id !== $player->team_id)
            : collect();
    }

    public function playerCanSwapTeams(?Player $player = null, ?PlayerState $player_state = null): bool
    {
        if ($player) {
            return ! in_array($player->id, $this->challenge->challenge_data['swapper_ids']);
        }

        if ($player_state) {
            return ! in_array($player_state->id, $this->challenge_state->challenge_data['swapper_ids']);
        }

        return true;
    }

    public function submit(Player $player, array $params): void
    {
        PlayerSubmittedPlayDirty::fire(
            player_id: $player->id,
            game_id: $player->game_id,
            challenge_id: $this->challenge->id,
            team_id: $player->team_id,
        );
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
    }

    public function onChallengeEnded(GameState $game_state) {
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

        foreach($team_pairs as $team_id => $paired_team_id) {
            $team = TeamState::load($team_id);

            $containsTeamId = $played_dirty->contains($team_id);
            $containsPairedTeamId = $played_dirty->contains($paired_team_id);

            if (
                $containsTeamId
                && $containsPairedTeamId
            ) {
                $team->addToScoreHistory(-20, 'Both teams played dirty');
            } elseif (
                $containsTeamId
                && ! $containsPairedTeamId
            ) {
                $team->addToScoreHistory(50, 'You played dirty and they did not');
            } elseif (
                ! $containsTeamId
                && ! $containsPairedTeamId
            ) {
                $team->addToScoreHistory(20, 'Neither team played dirty');
            }
        }
    }
}
