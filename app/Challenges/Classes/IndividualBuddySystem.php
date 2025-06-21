<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsPeckingOrderBallots;
use App\Challenges\Support\Traits\HasPeckingOrderBallots;
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
            'votes' => $this->challenge_state->game()->players()->mapWithKeys(fn ($p) => [$p->id => [
                'downvote_player_id' => null,
                'upvote_player_id' => null,
            ]])->toArray(),
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
            $buddy_id = $votes[$player->id]['upvote_player_id'];

            $buddy_upvoted_player = $buddy_id && $votes[$buddy_id]['upvote_player_id']
                ? $votes[$buddy_id]['upvote_player_id'] === $player->id
                : false;

            if ($buddy_upvoted_player) {
                $player->addToScoreHistory(1, '🤝 Found a buddy: '.PlayerState::load($buddy_id)->name, true);
            } else {
                $player->addToScoreHistory(0, '😔 Did not find a buddy', true);
            }
        });
    }
}
