<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsPeckingOrderBallots;
use App\Challenges\Support\Traits\HasPeckingOrderBallots;
use App\Models\Player;
use App\States\GameState;

class IndividualEquilibrium extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Equilibrium';

    const DESCRIPTION = 'The average score (not including hidde points) is {average}. You cannot upvote a player with a score higher than {average}, or downvote a player with a score lower than {average}.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_equilibrium';
    }

    public function dataArrayForState(): array
    {
        return [
            'votes' => [],
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $has_voted = $this->hasVoted($player);

        $average_score = $player->game->players->avg(fn ($p) => $p->score);

        $description = strtr(self::DESCRIPTION, [
            '{average}' => $average_score,
        ]);

        return $this->form()
            ->title(self::NAME)
            ->subtitle($description)
            ->when($has_voted, fn ($form) => $form->subtitle($this->voteDescription($player))
            )
            ->when(! $has_voted, fn ($form) => $form->peckingOrderBallot(
                upvote_targets: $this->upvoteTargets($player)->reject(fn ($p) => $p->score > $average_score),
                downvote_targets: $this->downvoteTargets($player)->reject(fn ($p) => $p->score < $average_score)
            )
            )
            ->build();
    }

    public function onChallengeEnded(GameState $game_state)
    {
        $this->applyVotesToScore($game_state);
    }
}
