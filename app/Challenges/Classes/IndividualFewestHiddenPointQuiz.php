<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsPeckingOrderBallots;
use App\Challenges\Support\Traits\HasPeckingOrderBallots;
use App\Events\PlayerSubmittedQuizGuess;
use App\Models\Player;
use App\States\GameState;

class IndividualFewestHiddenPointQuiz extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'A broken clock that is never right';

    const DESCRIPTION = 'All votes from this challenge will count toward hidden points. Guess which player will have the fewest hidden points at the end of this challenge. If you are correct, you will gain one hidden point.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_fewest_hidden_point_quiz';
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
                label: 'Guess which player had the fewest hidden points at the beginning of this challenge',
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
        $votes = $this->challenge_state->challenge_data['votes'];

        $players = $game_state->players();

        $players->each(function ($player) use ($votes) {
            $upvotes_received = collect($votes)
                ->filter(fn ($v) => $v['upvote_player_id'] === $player->id)
                ->count();

            $downvotes_received = collect($votes)
                ->filter(fn ($v) => $v['downvote_player_id'] === $player->id)
                ->count();

            if ($upvotes_received > 0) {
                $player->addToScoreHistory($upvotes_received, 'Received hidden upvotes', true);
            }

            if ($downvotes_received > 0) {
                $player->addToScoreHistory(-$downvotes_received, 'Received hidden downvotes', true);
            }
        });

        $hidden_points = $game_state->players()->mapWithKeys(fn ($p) => [$p->id => $p->score(include_hidden: true) - $p->score()]
        );

        $fewest_hidden_points = $hidden_points->min();

        $fewest_hidden_points_ids = $hidden_points->filter(fn ($hidden_points) => $hidden_points === $fewest_hidden_points)->keys();

        $game_state->players()->each(function ($player) use ($fewest_hidden_points_ids) {
            $guess_id = $this->challenge_state->challenge_data['quiz_submissions'][$player->id]['guess_player_id'];

            if ($fewest_hidden_points_ids->contains($guess_id)) {
                $player->addToScoreHistory(1, 'Correctly guessed the player with the fewest hidden points', true);
            }
        });
    }
}
