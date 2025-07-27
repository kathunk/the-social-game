<?php

namespace App\Challenges\Classes;

use App\Events\PlayerSubmittedPopulartiyContestVote;
use App\Models\Player;
use App\Models\Team;
use App\States\GameState;

class TeamPopularityContest extends BaseChallengeClass
{
    const NAME = 'Popularity Contest';

    const DESCRIPTION = 'You may upvote and downvote another team. Teams will get +1 point for each upvote, and -1 point for each downvote.';

    const TYPE = 'team';

    public static function key(): string
    {
        return 'team_popularity_contest';
    }

    public function dataArrayForState(): array
    {
        return ['votes' => []];
    }

    public function frontendComponent(Player $player): array
    {
        $existing_ballot = $this->challenge->challenge_data['votes'][$player->id] ?? null;

        if ($existing_ballot) {
            $upvote_team = Team::find($existing_ballot['upvote_team_id']);
            $downvote_team = Team::find($existing_ballot['downvote_team_id']);

            return $this->form()
                ->title(self::NAME)
                ->subtitle('🗳️ Upvoted '.$upvote_team->name.' and downvoted '.$downvote_team->name.'.')
                ->build();
        }

        $opposing_teams = $player->game->teams->filter(fn ($t) => $t->id !== $player->team_id);

        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->select(
                label: 'Upvote',
                options: $opposing_teams->mapWithKeys(fn ($team) => [$team->id => $team->name])->toArray(),
                property_name: 'upvote_team_id',
                placeholder: 'Select a team...',
                validation_rules: 'required|in:'.implode(',', $opposing_teams->pluck('id')->toArray()),
                validation_messages: ['required' => 'Team is required', 'in' => 'Team is invalid'],
            )->select(
                label: 'Downvote',
                options: $opposing_teams->mapWithKeys(fn ($team) => [$team->id => $team->name])->toArray(),
                property_name: 'downvote_team_id',
                placeholder: 'Select a team...',
                validation_rules: 'required|in:'.implode(',', $opposing_teams->pluck('id')->toArray()),
                validation_messages: ['required' => 'Team is required', 'in' => 'Team is invalid'],
            )
            ->buttonGroup()
            ->button('Vote', 'vote', ['upvote_team_id', 'downvote_team_id'])
            ->endGroup()
            ->build();
    }

    public function vote(Player $player, array $params)
    {
        PlayerSubmittedPopulartiyContestVote::fire(
            challenge_id: $this->challenge->id,
            game_id: $player->game->id,
            player_id: $player->id,
            downvote_team_id: $params['downvote_team_id'],
            upvote_team_id: $params['upvote_team_id'],
        );
    }

    public function onChallengeEnded(GameState $game_state)
    {
        $votes = $this->challenge_state->challenge_data['votes'];

        $teams = $game_state->teams();

        $teams->each(function ($team) use ($votes) {
            $upvotes_received = collect($votes)
                ->filter(fn ($v) => $v['upvote_team_id'] === $team->id)
                ->count();

            $downvotes_received = collect($votes)
                ->filter(fn ($v) => $v['downvote_team_id'] === $team->id)
                ->count();

            if ($upvotes_received > 0) {
                $team->addToScoreHistory(
                    icon: '👍',
                    points: $upvotes_received,
                    description: 'Received upvotes',
                );
            }

            if ($downvotes_received > 0) {
                $team->addToScoreHistory(
                    icon: '👎',
                    points: -$downvotes_received,
                    description: 'Received downvotes',
                );
            }
        });
    }
}
