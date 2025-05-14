<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsPeckingOrderBallots;
use App\Challenges\Support\Traits\HasPeckingOrderBallots;
use App\Events\PlayerSubmittedQuizGuess;
use App\Models\Player;
use App\States\GameState;

class IndividualMostTotalVotesQuiz extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Absolute value champion';

    const DESCRIPTION = 'Guess which player will receive the most votes this round, including upvotes and downvotes. If you are within 1 point of the correct score, you will gain one hidden point, that will not be revealed to your opponents until the end of the game.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_guess_most_total_votes_quiz';
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
        $has_guessed = $this->challenge->challenge_data['quiz_submissions'][$player->id]['guess_player_id'] !== null;
        $has_voted = $this->challenge->challenge_data['votes'][$player->id]['upvote_player_id'] !== null;

        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->when($has_guessed, fn ($form) => $form->subtitle('You have already guessed.')
            )
            ->when(! $has_guessed, fn ($form) => $form->select(
                property_name: 'guess_player_id',
                options: $players->mapWithKeys(fn ($p) => [$p->id => $p->name])->toArray(),
                label: 'Guess which player will receive the most votes this round',
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
            ->when($has_voted, fn ($form) => $form->subtitle('You have already voted.')
            )
            ->when(! $has_voted, fn ($form) => $form->peckingOrderBallot(
                upvote_targets: $players->reject(fn ($p) => $p->id === $player->id),
                downvote_targets: $players->reject(fn ($p) => $p->id === $player->id)
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

        $vote_counts = $game_state->players()->mapWithKeys(function ($player) use ($votes) {
            $upvotes = $votes->filter(fn ($v) => $v['upvote_player_id'] === $player->id)->count();
            $downvotes = $votes->filter(fn ($v) => $v['downvote_player_id'] === $player->id)->count();

            return [$player->id => $upvotes + $downvotes];
        });

        $max_votes = $vote_counts->max();

        $players_with_most_votes = $vote_counts->filter(fn ($count) => $count === $max_votes)->keys()->toArray();

        $game_state->players()->each(function ($player) use ($players_with_most_votes) {
            $guess_id = $this->challenge_state->challenge_data['quiz_submissions'][$player->id]['guess_player_id'];

            if (in_array($guess_id, $players_with_most_votes)) {
                $player->addToScoreHistory(1, 'Correctly guessed the player with the most votes', true);
            }
        });
    }
}
