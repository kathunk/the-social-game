<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsPeckingOrderBallots;
use App\Challenges\Support\Traits\HasPeckingOrderBallots;
use App\Events\PlayerChoseHopeOrFear;
use App\Models\Player;
use App\States\GameState;

class IndividualChooseHopeOrFear extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Choose Hope or Fear';

    const DESCRIPTION = 'If you choose hope and your score increases, you will gain a hidden point. If you choose fear and your score decreases, you will gain a hidden point.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_choose_hope_or_fear';
    }

    public function dataArrayForState(): array
    {
        return [
            'choices' => $this->challenge_state->game()->players()->mapWithKeys(fn ($p) => [$p->id => null])->toArray(),
            'votes' => $this->challenge_state->game()->players()->mapWithKeys(fn ($p) => [$p->id => [
                'downvote_player_id' => null,
                'upvote_player_id' => null,
            ]])->toArray(),
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $has_chosen = isset($this->challenge->challenge_data['choices'][$player->id])
            && $this->challenge->challenge_data['choices'][$player->id] !== null;
        $has_voted = $this->hasVoted($player);

        if ($has_chosen && $this->challenge->challenge_data['choices'][$player->id] === 'hope') {
            $choice_description = '🤞 Chose hope';
        } elseif ($has_chosen && $this->challenge->challenge_data['choices'][$player->id] === 'fear') {
            $choice_description = '😱 Chose fear';
        } else {
            $choice_description = null;
        }

        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->when($has_chosen, fn ($form) => $form->subtitle($choice_description)
            )
            ->when(! $has_chosen, fn ($form) => $form
                ->buttonGroup()
                ->button('Hope', 'choose_hope')
                ->button('Fear', 'choose_fear')
                ->endGroup()
            )
            ->when(! $has_chosen || ! $has_voted, fn ($form) => $form->divider()
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

    public function choose_hope(Player $player, array $params)
    {
        PlayerChoseHopeOrFear::fire(
            player_id: $player->id,
            challenge_id: $this->challenge->id,
            game_id: $this->challenge->game_id,
            choice: 'hope',
        );
    }

    public function choose_fear(Player $player, array $params)
    {
        PlayerChoseHopeOrFear::fire(
            player_id: $player->id,
            challenge_id: $this->challenge->id,
            game_id: $this->challenge->game_id,
            choice: 'fear',
        );
    }

    public function onChallengeEnded(
        GameState $game_state,
    ) {
        $this->applyVotesToScore($game_state);

        $choices = $this->challenge_state->challenge_data['choices'];

        $votes = $this->challenge_state->challenge_data['votes'];

        $players = $game_state->players();

        $players->each(function ($player) use ($votes, $choices) {
            $upvotes_received = collect($votes)
                ->filter(fn ($v) => $v['upvote_player_id'] === $player->id)
                ->count();

            $downvotes_received = collect($votes)
                ->filter(fn ($v) => $v['downvote_player_id'] === $player->id)
                ->count();

            $score_change = $upvotes_received - $downvotes_received;

            if ($choices[$player->id] === 'hope' && $score_change > 0) {
                $player->addToScoreHistory(1, '🤞 Chose hope', true);
            }

            if ($choices[$player->id] === 'hope' && $score_change <= 0) {
                $player->addToScoreHistory(0, '🤞 Chose hope but score did not increase', true);
            }

            if ($choices[$player->id] === 'fear' && $score_change < 0) {
                $player->addToScoreHistory(1, '😱 Chose fear', true);
            }

            if ($choices[$player->id] === 'fear' && $score_change >= 0) {
                $player->addToScoreHistory(0, '😱 Chose fear but score did not decrease', true);
            }
        });
    }
}
