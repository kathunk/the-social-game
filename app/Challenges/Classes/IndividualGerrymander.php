<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsPeckingOrderBallots;
use App\Challenges\Support\Traits\HasPeckingOrderBallots;
use App\Events\PlayerGerrymanderedOpponent;
use App\Models\Player;
use App\States\GameState;

class IndividualGerrymander extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Gerrymander';

    const DESCRIPTION = "Ballots count double this round. You may gerrymander an opponent. If at least one other player also gerrymanders them, the gerrymandered player's vote will not count.";

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_gerrymander';
    }

    public function dataArrayForState(): array
    {
        return [
            'gerrymandered_player_ids' => [],
            'votes' => [],
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $has_voted = $this->hasVoted($player);
        $gerrymandered_player_ids =
            $this->challenge->challenge_data['gerrymandered_player_ids'];
        $has_gerrymandered = array_key_exists(
            $player->id,
            $gerrymandered_player_ids
        );

        if ($has_gerrymandered) {
            $gerrymandered_player = Player::find(
                $gerrymandered_player_ids[$player->id]
            )->name;
        }

        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->when(
                $has_gerrymandered,
                fn ($form) => $form->subtitle(
                    '🚫 You have gerrymandered '.$gerrymandered_player.'.'
                )
            )
            ->when(
                ! $has_gerrymandered,
                fn ($form) => $form
                    ->select(
                        property_name: 'gerrymandered_player_id',
                        options: $this->challenge->game->players
                            ->reject(fn ($p) => $p->id === $player->id)
                            ->mapWithKeys(fn ($p) => [$p->id => $p->name])
                            ->toArray(),
                        label: 'Select a player to gerrymander',
                        placeholder: 'Select a player...',
                        validation_rules: 'required|in:'.
                            implode(
                                ',',
                                $this->challenge->game->players
                                    ->reject(fn ($p) => $p->id === $player->id)
                                    ->pluck('id')
                                    ->toArray()
                            ),
                        validation_messages: [
                            'required' => 'Must select a player',
                            'in' => 'Must select a valid player',
                        ]
                    )
                    ->buttonGroup()
                    ->button(
                        label: 'Gerrymander',
                        action: 'gerrymander',
                        properties_to_validate: ['gerrymandered_player_id']
                    )
                    ->endGroup()
            )
            ->when(
                ! $has_gerrymandered && ! $has_voted,
                fn ($form) => $form->divider()
            )
            ->when(
                $has_voted,
                fn ($form) => $form->subtitle($this->voteDescription($player))
            )
            ->when(
                ! $has_voted,
                fn ($form) => $form->peckingOrderBallot(
                    upvote_targets: $this->upvoteTargets($player),
                    downvote_targets: $this->downvoteTargets($player)
                )
            )
            ->build();
    }

    public function gerrymander(Player $player, array $params)
    {
        PlayerGerrymanderedOpponent::fire(
            player_id: $player->id,
            gerrymandered_player_id: (int) $params['gerrymandered_player_id'],
            challenge_id: $this->challenge->id,
            game_id: $player->game_id
        );
    }

    public function onChallengeEnded(GameState $game_state)
    {
        $gerrymandered_player_ids = collect(
            $this->challenge_state->challenge_data['gerrymandered_player_ids']
        );
        $votes = $this->challenge_state->challenge_data['votes'];
        $players = $game_state->players();

        $players->each(function ($player) use (
            $votes,
            $gerrymandered_player_ids
        ) {
            $upvotes_received = 0;
            $downvotes_received = 0;

            $votes = collect($votes)
                ->filter(
                    fn ($vote) => $vote['upvote_player_id'] === $player->id ||
                        $vote['downvote_player_id'] === $player->id
                )
                ->toArray();

            foreach ($votes as $voter_id => $vote) {
                $voter_is_gerrymandered =
                    collect($gerrymandered_player_ids)
                        ->filter(fn ($id) => $id === $voter_id)
                        ->count() > 1;

                if ($vote['upvote_player_id'] === $player->id) {
                    $upvotes_received += $voter_is_gerrymandered ? 0 : 1;
                }

                if ($vote['downvote_player_id'] === $player->id) {
                    $downvotes_received += $voter_is_gerrymandered ? 0 : 1;
                }
            }

            if ($upvotes_received > 0) {
                $player->addToScoreHistory(
                    icon: '👍',
                    points: $upvotes_received * 2,
                    description: 'Received doubled upvotes',
                );
            }

            if ($downvotes_received > 0) {
                $player->addToScoreHistory(
                    icon: '👎',
                    points: -$downvotes_received * 2,
                    description: 'Received doubled downvotes',
                );
            }
        });
    }
}
