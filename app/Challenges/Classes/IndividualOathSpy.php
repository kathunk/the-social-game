<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsPeckingOrderBallots;
use App\Challenges\Support\Traits\HasPeckingOrderBallots;
use App\Events\PlayerSpiedOpponents;
use App\Models\Player;
use App\Modifiers\Classes\BloodOaths;
use App\States\GameState;

class IndividualOathSpy extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Oath sleuth';

    const DESCRIPTION = 'You may incur -1 hidden point to learn the hidden score of 3 random players. One player from a Blood Oath, one from an Oath of Solitude, and one with no Oath.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_oath_spy';
    }

    public function isInvalidForTemplate(array $challenge_keys, array $modifier_keys, string $type, array $team_names)
    {
        if (! in_array(BloodOaths::key(), $modifier_keys)) {
            return 'Blood Oaths modifier is required to run this challenge';
        }

        return false;
    }

    public function dataArrayForState(): array
    {
        return [
            'information_bought' => $this->challenge_state->game()->players()->mapWithKeys(fn ($p) => [$p->id => null])->toArray(),
            'votes' => $this->challenge_state->game()->players()->mapWithKeys(fn ($p) => [$p->id => [
                'downvote_player_id' => null,
                'upvote_player_id' => null,
            ]])->toArray(),
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $information = $this->challenge->challenge_data['information_bought'][$player->id];
        $has_voted = $this->challenge->challenge_data['votes'][$player->id]['upvote_player_id'] !== null;

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
            ->when($has_voted, fn ($form) => $form->subtitle('You have already voted.')
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
        $players = $player->game->players->filter(fn ($p) => $p->id !== $player->id);
        $oath_data = $player->game->modifiers()->firstWhere('class_key', BloodOaths::key())->modifier_data;

        $blood_oath_players = $players->filter(fn ($p) => isset($oath_data['pairs'][$p->id]));
        $blood_oath_player = $blood_oath_players->isNotEmpty()
            ? $blood_oath_players->random()
            : null;

        $solitude_players = $players->filter(fn ($p) => collect($oath_data['lone_wolves'])->contains($p->id));
        $solitude_player = $solitude_players->isNotEmpty()
            ? $solitude_players->random()
            : null;

        $no_oath_players = $players->filter(fn ($p) => ! isset($oath_data['pairs'][$p->id]) && ! collect($oath_data['lone_wolves'])->contains($p->id));
        $no_oath_player = $no_oath_players->isNotEmpty()
            ? $no_oath_players->random()
            : null;

        $spied_opponents = [
            $blood_oath_player?->id,
            $solitude_player?->id,
            $no_oath_player?->id,
        ];

        $blood_oath_message = $blood_oath_player !== null
            ? $blood_oath_player->name.' is in a blood oath, with a true score of '.$blood_oath_player->hidden_score.'.'
            : 'There are no blood oath players to spy on.';

        $solitude_message = $solitude_player !== null
            ? $solitude_player->name.' is in an oath of solitude, with a true score of '.$solitude_player->hidden_score.'.'
            : 'No opponent is in an oath of solitude.';

        $no_oath_message = $no_oath_player !== null
            ? $no_oath_player->name.' has no Oath, with a true score of '.$no_oath_player->hidden_score.'.'
            : 'All players have an Oath.';

        PlayerSpiedOpponents::fire(
            player_id: $player->id,
            challenge_id: $this->challenge->id,
            game_id: $this->challenge->game_id,
            spied_opponent_ids: $spied_opponents,
            ui_message: 'You spied on your opponents. Here are their scores when you spied them. '.$blood_oath_message.' '.$solitude_message.' '.$no_oath_message,
            score_cost: 0,
            hidden_score_cost: 1,
        );
    }

    public function onChallengeEnded(GameState $game_state)
    {
        $this->applyVotesToScore($game_state);
    }
}
