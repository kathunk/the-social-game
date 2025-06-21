<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsPeckingOrderBallots;
use App\Challenges\Support\Traits\HasPeckingOrderBallots;
use App\Models\Player;
use App\States\GameState;

class IndividualFirstShallBeLast extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'First Shall Be Last';

    const DESCRIPTION = 'After votes are tallied at the end of this round, the player(s) at the top of the scoreboard will get -{player_count}) points. The player(s) at the bottom of the scoreboard will get +{player_count} points.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_first_shall_be_last';
    }

    public function dataArrayForState(): array
    {
        return [
            'votes' => $this->challenge_state->game()->players()->mapWithKeys(fn ($p) => [$p->id => [
                'downvote_player_id' => null,
                'upvote_player_id' => null,
            ]])->toArray(),
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $players = $player->game->players;
        $has_voted = $this->hasVoted($player);

        $player_count = $players->count();

        $description = strtr(self::DESCRIPTION, [
            '{player_count}' => $player_count,
        ]);

        return $this->form()
            ->title(self::NAME)
            ->subtitle($description)
            ->when($has_voted, fn ($form) => $form->subtitle($this->voteDescription($player))
            )
            ->when(! $has_voted, fn ($form) => $form->peckingOrderBallot(
                upvote_targets: $this->upvoteTargets($player),
                downvote_targets: $this->downvoteTargets($player)
            )
            )
            ->build();
    }

    public function onChallengeEnded(
        GameState $game_state,
    ) {
        $this->applyVotesToScore($game_state);

        $highest_score = $game_state->players()->max(fn ($p) => $p->score());
        $leader_ids = $game_state->players()->filter(fn ($p) => $p->score() === $highest_score)->pluck('id');
        $lowest_score = $game_state->players()->min(fn ($p) => $p->score());
        $loser_ids = $game_state->players()->filter(fn ($p) => $p->score() === $lowest_score)->pluck('id');

        $player_count = $game_state->player_ids->count();

        $game_state->players()->each(function ($player) use ($leader_ids, $loser_ids, $player_count) {
            if ($leader_ids->contains($player->id)) {
                $player->addToScoreHistory(-$player_count, '👇 First Shall Be Last');
            }

            if ($loser_ids->contains($player->id)) {
                $player->addToScoreHistory($player_count, '👆 Last Shall Be First');
            }
        });
    }
}
