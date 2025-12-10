<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsPeckingOrderBallots;
use App\Challenges\Support\Traits\HasPeckingOrderBallots;
use App\Events\PlayerBoughtSecurity;
use App\Models\Player;
use App\States\GameState;

class IndividualFirstShallBeLast extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'First Shall Be Last';

    const DESCRIPTION = 'After votes are tallied at the end of this round, the player(s) at the top of the scoreboard will get -{([# of points separating 1st and last] + [# of players])/2} points. The player(s) at the bottom of the scoreboard will get {([# of points separating 1st and last] + [# of players])/2} points. You may choose to incur -1 hidden point for Security: the first {# of players / 3} downvotes AND the first {# of players / 3} upvotes you receive this round will not count.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_first_shall_be_last';
    }

    public function dataArrayForState(): array
    {
        return [
            'secure_player_ids' => [],
            'votes' => [],
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $players = $player->game->players;
        $has_voted = $this->hasVoted($player);
        $has_bought_security = in_array($player->id, $this->challenge->challenge_data['secure_player_ids']);

        $player_count = $players->count();
        $score_gap = $players->max(fn ($p) => $p->score) - $players->min(fn ($p) => $p->score);

        $description = strtr(self::DESCRIPTION, [
            '{([# of points separating 1st and last] + [# of players])/2}' => (int) (($score_gap + $player_count) / 2),
            '{# of players / 3}' => (int) ($player_count / 3),
        ]);

        return $this->form()
            ->title(self::NAME)
            ->subtitle($description)
            ->when($has_bought_security, fn ($form) => $form->subtitle('🛡️ You have bought security.')
            )
            ->when(! $has_bought_security, fn ($form) => $form
                ->buttonGroup()
                ->button('Buy security', 'buy_security')
                ->endGroup()
            )
            ->when(! $has_bought_security || ! $has_voted, fn ($form) => $form->divider()
            )
            ->when($has_voted, fn ($form) => $form->subtitle($this->voteDescription($player))
            )
            ->when(! $has_voted, fn ($form) => $form->peckingOrderBallot(
                upvote_targets: $this->upvoteTargets($player),
                downvote_targets: $this->downvoteTargets($player)
            )
            )
            ->build();
    }

    public function buy_security(Player $player, array $params)
    {
        $player_count = $player->game->players->count();

        $votes_to_ignore = (int) ($player_count / 3);

        PlayerBoughtSecurity::fire(
            player_id: $player->id,
            cost_in_hidden_points: 1,
            cost_in_points: 0,
            downvotes_to_ignore: $votes_to_ignore,
            upvotes_to_ignore: $votes_to_ignore,
            challenge_id: $this->challenge->id,
            game_id: $player->game_id,
        );
    }

    public function onChallengeEnded(
        GameState $game_state,
    ) {
        $max_pre_vote_score = $game_state->players()->max(fn ($p) => $p->score());
        $min_pre_vote_score = $game_state->players()->min(fn ($p) => $p->score());
        $pre_vote_score_gap = $max_pre_vote_score - $min_pre_vote_score;
        $player_count = $game_state->player_ids->count();
        $point_reward = (int) (($pre_vote_score_gap + $player_count) / 2);

        $votes = $this->challenge_state->challenge_data['votes'];

        $votes_to_ignore = (int) ($player_count / 3);

        $game_state->players()->each(function ($player) use ($votes, $votes_to_ignore) {
            $has_bought_security = in_array($player->id, $this->challenge_state->challenge_data['secure_player_ids']);

            $upvotes_received = collect($votes)
                ->filter(fn ($v) => $v['upvote_player_id'] === $player->id)
                ->count();

            if ($has_bought_security) {
                $upvotes_received = max(0, $upvotes_received - $votes_to_ignore);
            }

            $downvotes_received = collect($votes)
                ->filter(fn ($v) => $v['downvote_player_id'] === $player->id)
                ->count();

            if ($has_bought_security) {
                $downvotes_received = max(0, $downvotes_received - $votes_to_ignore);
            }

            if ($upvotes_received > 0) {
                $player->addToScoreHistory(
                    icon: '👍',
                    points: $upvotes_received,
                    description: 'Received upvotes',
                );
            }

            if ($downvotes_received > 0) {
                $player->addToScoreHistory(
                    icon: '👎',
                    points: -$downvotes_received,
                    description: 'Received downvotes',
                );
            }
        });

        $highest_score = $game_state->players()->max(fn ($p) => $p->score());
        $leader_ids = $game_state->players()->filter(fn ($p) => $p->score() === $highest_score)->pluck('id');
        $lowest_score = $game_state->players()->min(fn ($p) => $p->score());
        $loser_ids = $game_state->players()->filter(fn ($p) => $p->score() === $lowest_score)->pluck('id');

        $game_state->players()->each(function ($player) use ($leader_ids, $loser_ids, $point_reward) {
            if ($leader_ids->contains($player->id)) {
                $player->addToScoreHistory(
                    icon: '👇',
                    points: -$point_reward,
                    description: 'First Shall Be Last',
                );
            }

            if ($loser_ids->contains($player->id)) {
                $player->addToScoreHistory(
                    icon: '👆',
                    points: $point_reward,
                    description: 'Last Shall Be First',
                );
            }
        });
    }
}
