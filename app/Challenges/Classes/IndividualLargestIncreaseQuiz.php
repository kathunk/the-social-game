<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsPeckingOrderBallots;
use App\Challenges\Support\Traits\HasPeckingOrderBallots;
use App\Events\PlayerSubmittedQuizGuess;
use App\Models\Player;
use App\States\GameState;

class IndividualLargestIncreaseQuiz extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Rising stars';

    const DESCRIPTION = 'Guess which player will have the largest score increase at the end of this round. If you are correct, you will gain one hidden point.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_largest_increase_quiz';
    }

    public function dataArrayForState(): array
    {
        return [
            'quiz_submissions' => $this->challenge_state->game()->players()->mapWithKeys(fn ($p) => [$p->id => ['guess_player_id' => null]])->toArray(),
            'votes' => $this->challenge_state->game()->players()->mapWithKeys(fn ($p) => [$p->id => [
                'downvote_player_id' => null,
                'upvote_player_id' => null,
            ]])->toArray(),
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $players = $player->game->players;
        $has_guessed = isset($this->challenge->challenge_data['quiz_submissions'][$player->id])
            && $this->challenge->challenge_data['quiz_submissions'][$player->id]['guess_player_id'] !== null;
        $has_voted = $this->hasVoted($player);

        $quiz_description = $has_guessed
            ? '🤔 Guessed that '.Player::find($this->challenge->challenge_data['quiz_submissions'][$player->id]['guess_player_id'])->name.
                ' will have the largest score increase at the end of this round.'
            : null;

        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->when($has_guessed, fn ($form) => $form->subtitle($quiz_description)
            )
            ->when(! $has_guessed, fn ($form) => $form->select(
                property_name: 'guess_player_id',
                options: $players->mapWithKeys(fn ($p) => [$p->id => $p->name])->toArray(),
                label: 'Name a player whose score will increase the most at the end of this round',
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
        $this->applyVotesToScore($game_state);

        $votes = collect($this->challenge_state->challenge_data['votes']);

        $score_changes = $game_state->players()->mapWithKeys(fn ($p) => [$p->id => $votes->where('upvote_player_id', $p->id)->count() - $votes->where('downvote_player_id', $p->id)->count()]);

        $largest_increase = $score_changes->max();

        $largest_increase_ids = $score_changes->filter(fn ($score_change) => $score_change === $largest_increase)->keys();

        $game_state->players()->each(function ($player) use ($largest_increase_ids) {
            $guess_id = $this->challenge_state->challenge_data['quiz_submissions'][$player->id]['guess_player_id'];

            if ($guess_id === null) {
                return;
            }

            if ($largest_increase_ids->contains($guess_id)) {
                $player->addToScoreHistory(1, '🤔 Correctly guessed that '.Player::find($guess_id)->name.' will have the largest score increase', true);
            } else {
                $player->addToScoreHistory(0, '🤔 Incorrectly guessed that '.Player::find($guess_id)->name.' will have the largest score increase', true);
            }
        });
    }
}
