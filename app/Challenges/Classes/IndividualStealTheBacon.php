<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsPeckingOrderBallots;
use App\Challenges\Support\Traits\HasPeckingOrderBallots;
use App\Events\PlayerStoleTheBacon;
use App\Models\Player;
use App\States\GameState;

class IndividualStealTheBacon extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Steal the Bacon';

    const DESCRIPTION = 'If you steal the bacon, you will receive -(total number of bacon stealers - {half_player_count}) hidden points. Choose wisely.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_steal_the_bacon';
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

        $has_chosen = isset($this->challenge->challenge_data['choices'][$player->id])
            && $this->challenge->challenge_data['choices'][$player->id] !== null;

        $has_voted = $this->hasVoted($player);

        $player_count = $players->count();

        $description = strtr(self::DESCRIPTION, [
            '{half_player_count}' => ceil($player_count / 2),
        ]);

        return $this->form()
            ->title(self::NAME)
            ->subtitle($description)
            ->when($has_chosen, fn ($form) => $form->subtitle('🥓 You stole the bacon.')
            )
            ->when(! $has_chosen, fn ($form) => $form
                ->buttonGroup()
                ->button('Steal the Bacon', 'steal_the_bacon')
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

    public function steal_the_bacon(Player $player, array $params)
    {
        PlayerStoleTheBacon::fire(
            player_id: $player->id,
            challenge_id: $this->challenge->id,
            game_id: $this->challenge->game_id,
        );
    }

    public function onChallengeEnded(
        GameState $game_state,
    ) {
        $this->applyVotesToScore($game_state);

        $choices = $this->challenge_state->challenge_data['choices'];
        $player_count = $game_state->player_ids->count();
        $number_of_stealers = collect($this->challenge_state->challenge_data['choices'])->filter(fn ($choice) => $choice === 'steal')->count();

        $game_state->players()->each(function ($player) use ($choices, $number_of_stealers, $player_count) {
            if (! isset($choices[$player->id])) {
                return;
            }

            if ($choices[$player->id] === 'steal') {
                $player->addToScoreHistory(-($number_of_stealers - ceil($player_count / 2)), '🥓 Stole the Bacon');
            }
        });
    }
}
