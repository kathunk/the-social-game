<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsPeckingOrderBallots;
use App\Challenges\Support\Traits\HasPeckingOrderBallots;
use App\Events\PlayerBecameInvisible;
use App\Events\PlayerSubmittedQuizGuess;
use App\Models\Player;
use App\States\GameState;

class IndividualMostHiddenPointQuiz extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Making moves in silence';

    const DESCRIPTION = 'You may choose to go invisible: all votes you receive this round will count as hidden points. Guess which player will have the most hidden points at the end of the challenge. If you are correct, you will gain one hidden point.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_most_hidden_point_quiz';
    }

    public function dataArrayForState(): array
    {
        return [
            'quiz_submissions' => [],
            'votes' => [],
            'invisible_player_ids' => [],
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $players = $player->game->players;
        $has_guessed = isset($this->challenge->challenge_data['quiz_submissions'][$player->id])
            && $this->challenge->challenge_data['quiz_submissions'][$player->id]['guess_player_id'] !== null;
        $has_voted = $this->hasVoted($player);
        $is_invisible = in_array($player->id, $this->challenge->challenge_data['invisible_player_ids']);

        $quiz_description = $has_guessed
            ? '🤔 Guessed that '.Player::find($this->challenge->challenge_data['quiz_submissions'][$player->id]['guess_player_id'])->name.
                ' will have the most hidden points at the end of this round.'
            : null;

        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->when(! $is_invisible, fn ($form) => $form->buttonGroup()
                ->button('Go invisible', 'go_invisible')
                ->endGroup()
                ->divider()
            )
            ->when($is_invisible, fn ($form) => $form->subtitle('🫥 You are invisible. All votes you receive this round will count as hidden points.'))
            ->when($has_guessed, fn ($form) => $form->subtitle($quiz_description)
            )
            ->when(! $has_guessed, fn ($form) => $form->select(
                property_name: 'guess_player_id',
                options: $players->mapWithKeys(fn ($p) => [$p->id => $p->name])->toArray(),
                label: 'Guess which player will have the most hidden points at the end of this challenge',
                placeholder: 'Select a player...',
                validation_rules: 'required|in:'.implode(',', $players->pluck('id')->toArray()),
                validation_messages: [
                    'required' => 'Must select a player',
                    'in' => 'Must select a valid player',
                ],
            )
                ->buttonGroup()
                ->button('Submit Guess', 'guess')
                ->endGroup()
            )
            ->when(! $has_guessed || ! $has_voted, fn ($form) => $form->divider()
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

    public function thing(Player $player, array $params)
    {
        dump('thing!');
    }

    public function go_invisible(Player $player, array $params)
    {
        PlayerBecameInvisible::fire(
            player_id: $player->id,
            challenge_id: $this->challenge->id,
            game_id: $this->challenge->game_id,
        );
    }

    public function guess(Player $player, array $params)
    {
        PlayerSubmittedQuizGuess::fire(
            player_id: $player->id,
            challenge_id: $this->challenge->id,
            game_id: $this->challenge->game_id,
            guess: ['guess_player_id' => $params['guess_player_id']],
        );
    }

    public function onChallengeEnded(
        GameState $game_state,
    ) {
        $votes = $this->challenge_state->challenge_data['votes'];
        $invisible_player_ids = $this->challenge_state->challenge_data['invisible_player_ids'];
        $players = $game_state->players();

        $players->each(function ($player) use ($votes, $invisible_player_ids) {
            $upvotes_received = collect($votes)
                ->filter(fn ($v) => $v['upvote_player_id'] === $player->id)
                ->count();

            $downvotes_received = collect($votes)
                ->filter(fn ($v) => $v['downvote_player_id'] === $player->id)
                ->count();

            $player_is_invisible = in_array($player->id, $invisible_player_ids);

            if ($upvotes_received > 0) {
                $text = $player_is_invisible ? 'Received hidden upvotes' : 'Received upvotes';
                $player->addToScoreHistory(
                    icon: '👍',
                    points: $upvotes_received,
                    description: $text,
                    is_hidden: $player_is_invisible,
                );
            }

            if ($downvotes_received > 0) {
                $text = $player_is_invisible ? 'Received hidden downvotes' : 'Received downvotes';
                $player->addToScoreHistory(
                    icon: '👎',
                    points: -$downvotes_received,
                    description: $text,
                    is_hidden: $player_is_invisible,
                );
            }
        });

        $hidden_points = $game_state->players()->mapWithKeys(fn ($p) => [$p->id => $p->score(include_hidden: true) - $p->score()]
        );

        $most_hidden_points = $hidden_points->max();

        $most_hidden_points_ids = $hidden_points->filter(fn ($hidden_points) => $hidden_points === $most_hidden_points)->keys();

        $game_state->players()->each(function ($player) use ($most_hidden_points_ids) {
            if (! isset($this->challenge_state->challenge_data['quiz_submissions'][$player->id])) {
                return;
            }

            $guess_id = $this->challenge_state->challenge_data['quiz_submissions'][$player->id]['guess_player_id'];

            if ($guess_id === null) {
                return;
            }

            if ($most_hidden_points_ids->contains($guess_id)) {
                $player->addToScoreHistory(
                    icon: '🤔',
                    points: 1,
                    description: 'Correctly guessed that '.Player::find($guess_id)->name.' will have the most hidden points',
                    is_hidden: true,
                );
            } else {
                $player->addToScoreHistory(
                    icon: '🤔',
                    points: 0,
                    description: 'Incorrectly guessed that '.Player::find($guess_id)->name.' will have the most hidden points',
                    is_hidden: true,
                );
            }
        });
    }
}
