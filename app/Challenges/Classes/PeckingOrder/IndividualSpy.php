<?php

namespace App\Challenges\Classes\PeckingOrder;

use App\Challenges\Classes\BaseChallengeClass;
use App\Challenges\Support\PeckingOrder\SupportsPeckingOrderBallots;
use App\Challenges\Support\PeckingOrder\HasPeckingOrderBallots;
use App\Events\PeckingOrder\PlayerSpiedOpponents;
use App\Models\Player;
use App\States\GameState;

class IndividualSpy extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Sleuth';

    const DESCRIPTION = 'You may incur -1 hidden point to learn the hidden score of 3 random players.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_spy';
    }

    public function dataArrayForState(): array
    {
        return [
            'information_bought' => [],
            'votes' => [],
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $information = isset($this->challenge->challenge_data['information_bought'][$player->id])
            ? $this->challenge->challenge_data['information_bought'][$player->id]
            : null;
        $has_voted = $this->hasVoted($player);

        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->when($information !== null, fn ($form) => $form->subtitle($information)
            )
            ->when($information === null, fn ($form) => $form
                ->buttonGroup()
                ->button(
                    label: 'Buy Information',
                    action: 'buy_information',
                    properties_to_validate: [],
                )
                ->endGroup()
            )
            ->when(! $information || ! $has_voted, fn ($form) => $form->divider()
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

    public function buy_information(Player $player, array $params)
    {
        $players = $player->game->players->filter(fn ($p) => $p->id !== $player->id)->random(3);

        $ui_message = $players->map(fn ($p) => $p->name.' has a hidden score of '.$p->hidden_score)->implode(', ');

        PlayerSpiedOpponents::fire(
            player_id: $player->id,
            challenge_id: $this->challenge->id,
            game_id: $this->challenge->game_id,
            spied_opponent_ids: $players->pluck('id')->toArray(),
            ui_message: '🔍 You spied on your opponents. Here are their scores when you spied them: '.$ui_message.'.',
            score_cost: 0,
            hidden_score_cost: 1,
        );
    }

    public function onChallengeEnded(GameState $game_state)
    {
        $this->applyVotesToScore($game_state);
    }
}
