<?php

namespace App\Challenges\PeckingOrder;

use App\Challenges\BaseChallengeClass;
use App\Challenges\Support\PeckingOrder\SupportsPeckingOrderBallots;
use App\Challenges\Support\PeckingOrder\HasPeckingOrderBallots;
use App\Events\PeckingOrder\PlayerSubmittedQuizGuess;
use App\Models\Player;
use App\States\GameState;

class IndividualHighScoreQuiz extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Belle of the ball';

    const DESCRIPTION = 'Guess which player will be at the top of the scoreboard at the end of this round. If you are correct, you will gain one hidden point.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_high_score_quiz';
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
        $players = $player->game->players;
        $has_guessed = isset($this->challenge->challenge_data['quiz_submissions'][$player->id])
            && $this->challenge->challenge_data['quiz_submissions'][$player->id]['guess_player_id'] !== null;
        $has_voted = $this->hasVoted($player);

        $quiz_description = $has_guessed
            ? '🤔 Guessed that '.Player::find($this->challenge->challenge_data['quiz_submissions'][$player->id]['guess_player_id'])->name.
                ' will be at the top of the scoreboard at the end of this round.'
            : null;

        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->when($has_guessed, fn ($form) => $form->subtitle($quiz_description)
            )
            ->when(! $has_guessed, fn ($form) => $form->select(
                property_name: 'guess_player_id',
                options: $players->mapWithKeys(fn ($p) => [$p->id => $p->name])->toArray(),
                label: 'Guess which player will be at the top of the scoreboard',
                placeholder: 'Select a player...',
                validation_rules: 'required|in:'.implode(',', $players->pluck('id')->toArray()),
                validation_messages: [
                    'required' => 'Must select a player',
                    'in' => 'Must select a valid player',
                ],
            )
                ->buttonGroup()
                ->button(
                    label: 'Submit Guess',
                    action: 'guess',
                    properties_to_validate: ['guess_player_id'],
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
            guess: ['guess_player_id' => $params['guess_player_id']],
        );
    }

    public function onChallengeEnded(
        GameState $game_state,
    ) {
        $this->applyVotesToScore($game_state);

        $highest_score = $game_state->players()->max(fn ($p) => $p->score());

        $leader_ids = $game_state->players()->filter(fn ($p) => $p->score() === $highest_score)->pluck('id');

        $game_state->players()->each(function ($player) use ($leader_ids) {
            if (! isset($this->challenge_state->challenge_data['quiz_submissions'][$player->id])) {
                return;
            }

            $guess_id = $this->challenge_state->challenge_data['quiz_submissions'][$player->id]['guess_player_id'];

            if ($guess_id === null) {
                return;
            }

            if ($leader_ids->contains($guess_id)) {
                $player->addToScoreHistory(
                    icon: '🤔',
                    points: 1,
                    description: 'Correctly guessed that '.Player::find($guess_id)->name.' will be at the top of the scoreboard',
                    is_hidden: true,
                );
            } else {
                $player->addToScoreHistory(
                    icon: '🤔',
                    points: 0,
                    description: 'Incorrectly guessed that '.Player::find($guess_id)->name.' will be at the top of the scoreboard',
                    is_hidden: true,
                );
            }
        });
    }
}
