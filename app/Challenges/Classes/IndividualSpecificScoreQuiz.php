<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsPeckingOrderBallots;
use App\Challenges\Support\Traits\HasPeckingOrderBallots;
use App\Events\PlayerSubmittedQuizGuess;
use App\Models\Player;
use App\States\GameState;

class IndividualSpecificScoreQuiz extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Guess your score';

    const DESCRIPTION = 'Guess what your score will be on the scoreboard at the beginning of the next round. If you are within 1 point of the correct score, you will gain one hidden point.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_guess_specific_score_quiz';
    }

    public function dataArrayForState(): array
    {
        return [
            'quiz_submissions' => [],
            'votes' => [],
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $has_guessed = isset($this->challenge->challenge_data['quiz_submissions'][$player->id])
            && $this->challenge->challenge_data['quiz_submissions'][$player->id]['guess_score'] !== null;
        $has_voted = $this->hasVoted($player);

        $quiz_description = $has_guessed
            ? '🤔 Guessed that your score will be '.$this->challenge->challenge_data['quiz_submissions'][$player->id]['guess_score'].'.'
            : null;

        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->when($has_guessed, fn ($form) => $form->subtitle($quiz_description)
            )
            ->when(! $has_guessed, fn ($form) => $form->input(
                property_name: 'guess_score',
                label: 'Guess your score',
                placeholder: 'Enter your guess...',
                validation_rules: 'required|integer',
                validation_messages: [
                    'required' => 'Pick a number, any number',
                    'integer' => "What part of 'number' aren't we understanding?",
                ],
            )
                ->buttonGroup()
                ->button(
                    label: 'Submit Guess',
                    action: 'guess',
                    properties_to_validate: ['guess_score'],
                )
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
            guess: ['guess_score' => $params['guess_score']],
        );
    }

    public function onChallengeEnded(
        GameState $game_state,
    ) {
        $this->applyVotesToScore($game_state);

        $player_scores = $game_state->players()->mapWithKeys(fn ($p) => [$p->id => $p->score(include_hidden: false)]);

        $game_state->players()->each(function ($player) use ($player_scores) {
            if (! isset($this->challenge_state->challenge_data['quiz_submissions'][$player->id])) {
                return;
            }

            $guess_score = $this->challenge_state->challenge_data['quiz_submissions'][$player->id]['guess_score'];

            if ($guess_score === null) {
                return;
            }

            if ($guess_score >= $player_scores[$player->id] - 1 && $guess_score <= $player_scores[$player->id] + 1) {
                $player->addToScoreHistory(1, '🤔 Correctly guessed their score was within 1 point of '.$guess_score, true);
            } else {
                $player->addToScoreHistory(0, '🤔 Incorrectly guessed their score was within 1 point of '.$guess_score, true);
            }
        });
    }
}
