<?php

namespace App\Challenges\Classes\PeckingOrder;

use App\Challenges\Classes\BaseChallengeClass;
use App\Challenges\Support\PeckingOrder\SupportsPeckingOrderBallots;
use App\Challenges\Support\PeckingOrder\HasPeckingOrderBallots;
use App\Events\PeckingOrder\PlayerBoughtImmunity;
use App\Models\Player;
use App\States\GameState;

class IndividualDoubleTrouble extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Double Trouble';

    const DESCRIPTION = 'All ballots count double this round. However, you may take -2 hidden points to block all downvotes you receive.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_double_trouble';
    }

    public function dataArrayForState(): array
    {
        return [
            'immune_player_ids' => [],
            'votes' => [],
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $has_bought_immunity = in_array($player->id, $this->challenge->challenge_data['immune_player_ids']);
        $has_voted = $this->hasVoted($player);

        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->when($has_bought_immunity, fn ($form) => $form->subtitle('🛡️ You have bought immunity.')
            )
            ->when(! $has_bought_immunity, fn ($form) => $form
                ->buttonGroup()
                ->button('Buy immunity', 'buy_immunity')
                ->endGroup()
            )
            ->when(! $has_bought_immunity || ! $has_voted, fn ($form) => $form->divider()
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

    public function buy_immunity(Player $player, array $params)
    {
        PlayerBoughtImmunity::fire(
            player_id: $player->id,
            cost_in_hidden_points: 2,
            cost_in_points: 0,
            challenge_id: $this->challenge->id,
            game_id: $player->game_id,
        );
    }

    public function onChallengeEnded(
        GameState $game_state,
    ) {
        $immune_player_ids = collect($this->challenge_state->challenge_data['immune_player_ids']);

        $votes = $this->challenge_state->challenge_data['votes'];

        $players = $game_state->players();

        $players->each(function ($player) use ($votes, $immune_player_ids) {
            $upvotes_received = collect($votes)
                ->filter(fn ($v) => $v['upvote_player_id'] === $player->id)
                ->count();

            $downvotes_received = collect($votes)
                ->filter(fn ($v) => $v['downvote_player_id'] === $player->id)
                ->count();

            if ($upvotes_received > 0) {
                $player->addToScoreHistory(
                    icon: '👍',
                    points: $upvotes_received * 2,
                    description: 'Received doubled upvotes',
                );
            }

            if ($immune_player_ids->contains($player->id) && $downvotes_received > 0) {
                $player->addToScoreHistory(
                    icon: '🛡️',
                    points: 0,
                    description: 'Blocked '.$downvotes_received * 2 .' downvotes',
                );
            }

            if (! $immune_player_ids->contains($player->id) && $downvotes_received > 0) {
                $player->addToScoreHistory(
                    icon: '👎',
                    points: -$downvotes_received * 2,
                    description: 'Received doubled downvotes',
                );
            }
        });
    }
}
