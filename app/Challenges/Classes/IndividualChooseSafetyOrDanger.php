<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsPeckingOrderBallots;
use App\Challenges\Support\Traits\HasPeckingOrderBallots;
use App\Events\PlayerChoseSafetyOrDanger;
use App\Models\Player;
use App\States\GameState;

class IndividualChooseSafetyOrDanger extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Choose Safety or Danger';

    const DESCRIPTION = 'If you choose safety, no downvotes will count against you this round. If you choose danger, you will gain 2 hidden points, but all downvotes you receive will count double.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_choose_safety_or_danger';
    }

    public function dataArrayForState(): array
    {
        return [
            'choices' => [],
            'votes' => [],
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $has_chosen = isset($this->challenge->challenge_data['choices'][$player->id])
            && $this->challenge->challenge_data['choices'][$player->id] !== null;
        $has_voted = $this->hasVoted($player);

        if ($has_chosen && $this->challenge->challenge_data['choices'][$player->id] === 'safety') {
            $choice_description = '☺️ Chose safety';
        } elseif ($has_chosen && $this->challenge->challenge_data['choices'][$player->id] === 'danger') {
            $choice_description = '☠️ Chose danger';
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
                ->button('Safety', 'choose_safety')
                ->button('Danger', 'choose_danger')
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

    public function choose_safety(Player $player, array $params)
    {
        PlayerChoseSafetyOrDanger::fire(
            player_id: $player->id,
            challenge_id: $this->challenge->id,
            game_id: $this->challenge->game_id,
            choice: 'safety',
        );
    }

    public function choose_danger(Player $player, array $params)
    {
        PlayerChoseSafetyOrDanger::fire(
            player_id: $player->id,
            challenge_id: $this->challenge->id,
            game_id: $this->challenge->game_id,
            choice: 'danger',
        );
    }

    public function onChallengeEnded(
        GameState $game_state,
    ) {
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

            if ($upvotes_received > 0) {
                $player->addToScoreHistory($upvotes_received, '👍 Received upvotes');
            }

            $did_not_make_choice = ! isset($choices[$player->id]) || $choices[$player->id] === null;

            if ($did_not_make_choice && $downvotes_received > 0) {
                $player->addToScoreHistory(-$downvotes_received, '👎 Received '.($downvotes_received === 1 ? 'downvote' : 'downvotes'));
            }

            if ($did_not_make_choice) {
                return;
            }

            if ($choices[$player->id] === 'danger' && $downvotes_received > 0) {
                $player->addToScoreHistory(-$downvotes_received * 2, '👎 Received '.$downvotes_received.' '.($downvotes_received === 1 ? 'downvote' : 'downvotes').' after choosing danger');
            }

            if ($choices[$player->id] === 'danger') {
                $player->addToScoreHistory(2, '☠️ Chose danger', true);
            }

            if ($choices[$player->id] === 'safety' && $downvotes_received > 0) {
                $player->addToScoreHistory(0, '☺️ Blocked '.$downvotes_received.' '.($downvotes_received === 1 ? 'downvote' : 'downvotes').' after choosing safety');
            }
        });
    }
}
