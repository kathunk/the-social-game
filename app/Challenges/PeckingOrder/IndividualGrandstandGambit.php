<?php

namespace App\Challenges\PeckingOrder;

use App\Challenges\BaseChallengeClass;
use App\Challenges\Support\PeckingOrder\SupportsPeckingOrderBallots;
use App\Challenges\Support\PeckingOrder\HasPeckingOrderBallots;
use App\Events\PeckingOrder\PlayerPlayedGrandstandGambit;
use App\Models\Player;
use App\States\GameState;

class IndividualGrandstandGambit extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Grandstand Gambit';

    const DESCRIPTION = 'You may give yourself 5 points. If you do this and end up at the top of the scoreboard, your score will reset to 0. If you do not take these 5 points, you will receive 1 hidden point.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_grandstand_gambit';
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

        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->when($has_chosen, fn ($form) => $form->subtitle('📈 You will gain 5 points.')
            )
            ->when(! $has_chosen, fn ($form) => $form
                ->buttonGroup()
                ->button('Gain 5 points', 'gain_5_points')
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

    public function gain_5_points(Player $player, array $params)
    {
        PlayerPlayedGrandstandGambit::fire(
            player_id: $player->id,
            challenge_id: $this->challenge->id,
            game_id: $this->challenge->game_id,
            points: 5,
        );
    }

    public function onChallengeEnded(
        GameState $game_state,
    ) {
        $this->applyVotesToScore($game_state);

        $choices = $this->challenge_state->challenge_data['choices'];

        $players = $game_state->players();

        $players->each(function ($player) use ($choices) {
            if (! isset($choices[$player->id]) || $choices[$player->id] === null) {
                $player->addToScoreHistory(
                    icon: '🫥',
                    points: 1,
                    description: 'Did not take Grandstand Gambit',
                    is_hidden: true,
                );

                return;
            }

            $player->addToScoreHistory(
                icon: '📈',
                points: 5,
                description: 'Grandstand Gambit',
            );
        });

        $highest_score = $players->max(fn ($p) => $p->score());

        $leader_ids = $players->filter(fn ($p) => $p->score() === $highest_score)->pluck('id');

        $players->each(function ($player) use ($choices, $leader_ids) {
            if (! isset($choices[$player->id]) || $choices[$player->id] === null) {
                return;
            }

            if ($choices[$player->id] !== null && $leader_ids->contains($player->id)) {
                $player->addToScoreHistory(
                    icon: '😩',
                    points: -$player->score(),
                    description: 'Grandstand Gambit too close to the sun',
                );
            }
        });
    }
}
