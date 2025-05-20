<?php

namespace App\Challenges\Classes;

use App\Models\Player;
use App\States\GameState;
use App\Events\PlayerChoseHopeOrFear;
use App\Events\PlayerPlayedGrandstandGambit;
use App\Challenges\Support\Traits\HasPeckingOrderBallots;
use App\Challenges\Support\Interfaces\SupportsPeckingOrderBallots;

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
            'choices' => $this->challenge_state->game()->players()->mapWithKeys(fn ($p) => [$p->id => null])->toArray(),
            'votes' => $this->challenge_state->game()->players()->mapWithKeys(fn ($p) => [$p->id => [
                'downvote_player_id' => null,
                'upvote_player_id' => null,
            ]])->toArray(),
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $players = $player->game->players;
        $has_chosen = $this->challenge->challenge_data['choices'][$player->id] !== null;
        $has_voted = $this->challenge->challenge_data['votes'][$player->id]['upvote_player_id'] !== null;

        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->when($has_chosen, fn ($form) => $form->subtitle('You have made your choice.')
            )
            ->when(! $has_chosen, fn ($form) => $form
                ->buttonGroup()
                ->button('Gain 5 points', 'gain_5_points')
                ->endGroup()
            )
            ->when(! $has_chosen || ! $has_voted, fn ($form) => $form->divider()
            )
            ->when($has_voted, fn ($form) => $form->subtitle('You have already voted.')
            )
            ->when(! $has_voted, fn ($form) => $form->peckingOrderBallot(
                upvote_targets: $players->reject(fn ($p) => $p->id === $player->id),
                downvote_targets: $players->reject(fn ($p) => $p->id === $player->id)
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

        $highest_score = $players->max(fn ($p) => $p->score());

        $leader_ids = $players->filter(fn ($p) => $p->score() === $highest_score)->pluck('id');

        $players->each(function ($player) use ($choices, $leader_ids) {
            if ($choices[$player->id] !== null && $leader_ids->contains($player->id)) {
                $player->addToScoreHistory(-$player->score(), 'Grandstand Gambit too close to the sun', true);
            }
        });
    }
}
