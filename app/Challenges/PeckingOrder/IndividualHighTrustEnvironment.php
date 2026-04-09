<?php

namespace App\Challenges\PeckingOrder;

use App\Challenges\BaseChallengeClass;
use App\Challenges\Support\PeckingOrder\SupportsPeckingOrderBallots;
use App\Challenges\Support\PeckingOrder\HasPeckingOrderBallots;
use App\Events\Laracon2025\PlayerTripledOpponentsVote;
use App\Models\Player;
use App\States\GameState;

class IndividualHighTrustEnvironment extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'High Trust Environment';

    const DESCRIPTION = "You may triple the value of another player's vote. If you do, you will not be able to vote.";

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_high_trust_environment';
    }

    public function dataArrayForState(): array
    {
        return [
            'tripled_player_ids' => [],
            'votes' => [],
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $players = $player->game->players;
        $has_voted = $this->hasVoted($player);
        $has_opted_out = array_key_exists($player->id, $this->challenge->challenge_data['tripled_player_ids']);

        if ($has_opted_out) {
            $tripled_player = Player::find($this->challenge->challenge_data['tripled_player_ids'][$player->id])->name;
        }

        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->when($has_opted_out, fn ($form) => $form->subtitle('💪 You have tripled the value of '.$tripled_player.'\'s vote.')
            )
            ->when($has_voted, fn ($form) => $form->subtitle($this->voteDescription($player))
            )
            ->when(! $has_opted_out && ! $has_voted, fn ($form) => $form->select(
                property_name: 'tripled_player_id',
                options: $players->reject(fn ($p) => $p->id === $player->id)->mapWithKeys(fn ($p) => [$p->id => $p->name])->toArray(),
                label: 'Select a player to triple the value of their vote',
                placeholder: 'Select a player...',
                validation_rules: 'required|in:'.implode(',', $players->reject(fn ($p) => $p->id === $player->id)->pluck('id')->toArray()),
                validation_messages: [
                    'required' => 'Must select a player',
                    'in' => 'Must select a valid player',
                ],
            )
                ->buttonGroup()
                ->button(
                    label: 'Triple Vote',
                    action: 'triple_vote',
                    properties_to_validate: ['tripled_player_id'],
                )
                ->endGroup()
                ->divider()
                ->peckingOrderBallot(
                    upvote_targets: $this->upvoteTargets($player),
                    downvote_targets: $this->downvoteTargets($player)
                )
            )
            ->build();
    }

    public function triple_vote(Player $player, array $params)
    {
        PlayerTripledOpponentsVote::fire(
            player_id: $player->id,
            challenge_id: $this->challenge->id,
            game_id: $this->challenge->game_id,
            tripled_player_id: (int) $params['tripled_player_id'],
        );
    }

    public function onChallengeEnded(
        GameState $game_state,
    ) {
        $votes = $this->challenge_state->challenge_data['votes'];

        $tripled_player_ids = $this->challenge_state->challenge_data['tripled_player_ids'];

        $players = $game_state->players();

        $players->each(function ($player) use ($votes, $tripled_player_ids) {
            $upvotes_received = 0;
            $downvotes_received = 0;

            $votes = collect($votes)
                ->filter(fn ($vote) => $vote['upvote_player_id'] === $player->id || $vote['downvote_player_id'] === $player->id)
                ->toArray();

            foreach ($votes as $voter_id => $vote) {
                $multiplier = max(1, collect($tripled_player_ids)->filter(fn ($id) => $id === $voter_id)->count() * 3);

                if ($vote['upvote_player_id'] === $player->id) {
                    $upvotes_received += $multiplier;
                }

                if ($vote['downvote_player_id'] === $player->id) {
                    $downvotes_received += $multiplier;
                }
            }

            if ($upvotes_received > 0) {
                $player->addToScoreHistory(
                    icon: '👍',
                    points: $upvotes_received,
                    description: 'Received upvotes',
                );
            }

            if ($downvotes_received > 0) {
                $player->addToScoreHistory(
                    icon: '👎',
                    points: -$downvotes_received,
                    description: 'Received downvotes',
                );
            }
        });
    }
}
