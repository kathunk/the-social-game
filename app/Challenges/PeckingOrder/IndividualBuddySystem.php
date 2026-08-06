<?php

namespace App\Challenges\PeckingOrder;

use App\Challenges\BaseChallengeClass;
use App\Challenges\Support\PeckingOrder\SupportsPeckingOrderBallots;
use App\Challenges\Support\PeckingOrder\HasPeckingOrderBallots;
use App\Models\Player;
use App\States\GameState;
use App\States\PlayerState;

class IndividualBuddySystem extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Buddy System';

    const DESCRIPTION = 'If the the player you upvote this round also upvotes you, you will both receive a hidden point.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_buddy_system';
    }

    public function dataArrayForState(): array
    {
        return [
            'votes' => [],
        ];
    }

    public function frontendComponent(Player $player): array
    {
        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->when($this->hasVoted($player), fn ($form) => $form->subtitle($this->voteDescription($player))
            )
            ->when(! $this->hasVoted($player), fn ($form) => $form->peckingOrderBallot(
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

        $votes = collect($this->challenge_state->challenge_data['votes']);

        $game_state->players()->each(function ($player) use ($votes) {
            if (! isset($votes[$player->id])) {
                return;
            }

            $buddy_id = $votes[$player->id]['upvote_player_id'];

            if (! isset($votes[$buddy_id])) {
                return;
            }

            $buddy_upvoted_player = $buddy_id && $votes[$buddy_id]['upvote_player_id']
                ? $votes[$buddy_id]['upvote_player_id'] === $player->id
                : false;

            if ($buddy_upvoted_player) {
                $player->addToScoreHistory(
                    icon: '🤝',
                    points: 1,
                    description: 'Found a buddy: '.PlayerState::load($buddy_id)->name,
                    is_hidden: true,
                );
            } else {
                $player->addToScoreHistory(
                    icon: '😔',
                    points: 0,
                    description: 'Did not find a buddy',
                    is_hidden: true,
                );
            }
        });
    }
}
