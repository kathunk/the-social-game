<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsPeckingOrderBallots;
use App\Challenges\Support\Traits\HasPeckingOrderBallots;
use App\Events\PlayerSubmittedQuizGuess;
use App\Models\Player;
use App\Modifiers\Classes\BloodOaths;
use App\States\GameState;
use App\States\PlayerState;

class IndividualOathQuiz extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Prior commitments';

    const DESCRIPTION = 'Select a player, and guess whether they are in a blood oath, or an oath of solitude. If you are correct, you will gain one hidden point, and they will receive -1 hidden point.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_oath_quiz';
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
        $has_voted = $this->hasVoted($player);

        if (isset($this->challenge->challenge_data['quiz_submissions'][$player->id]['oath_type']) 
            && $this->challenge->challenge_data['quiz_submissions'][$player->id]['oath_type'] === 'blood_oath') {
            $oath_type_description = 'a Blood Oath';
        } elseif (isset($this->challenge->challenge_data['quiz_submissions'][$player->id]['oath_type']) && $this->challenge->challenge_data['quiz_submissions'][$player->id]['oath_type'] === 'oath_of_solitude') {
            $oath_type_description = 'an Oath of Solitude';
        } else {
            $oath_type_description = null;
        }

        $quiz_description = $has_guessed
            ? '🤔 Guessed that '. Player::find($this->challenge->challenge_data['quiz_submissions'][$player->id]['guess_player_id'])->name.
                ' is in '. $oath_type_description . '.'
            : null;

        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->when($has_guessed, fn ($form) => $form->subtitle($quiz_description)
            )
            ->when(! $has_guessed, fn ($form) => $form->select(
                property_name: 'guess_player_id',
                options: $players->mapWithKeys(fn ($p) => [$p->id => $p->name])->toArray(),
                label: 'Select a player',
                placeholder: 'Select a player...',
                validation_rules: 'required|in:'.implode(',', $players->pluck('id')->toArray()),
                validation_messages: [
                    'required' => 'Must select a player',
                    'in' => 'Must select a valid player',
                ],
            )
                ->select(
                    property_name: 'oath_type',
                    options: ['blood_oath' => 'Blood Oath', 'oath_of_solitude' => 'Oath of Solitude'],
                    label: 'Select an oath type',
                    placeholder: 'Select an oath type...',
                    validation_rules: 'required|in:blood_oath,oath_of_solitude',
                    validation_messages: [
                        'required' => 'Must select an oath type',
                        'in' => 'Must select a valid oath type',
                    ],
                )
                ->buttonGroup()
                ->button(
                    label: 'Submit Guess',
                    action: 'guess',
                    properties_to_validate: ['guess_player_id', 'oath_type'],
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
            guess: [
                'guess_player_id' => $params['guess_player_id'],
                'oath_type' => $params['oath_type'],
            ],
        );
    }

    public function onChallengeEnded(
        GameState $game_state,
    ) {
        $this->applyVotesToScore($game_state);

        $oath_data = $game_state->modifiers()->firstWhere('class_key', BloodOaths::key())->modifier_data;

        $game_state->players()->each(function ($player) use ($oath_data) {
            $guess = $this->challenge_state->challenge_data['quiz_submissions'][$player->id];

            if ($guess['guess_player_id'] === null || $guess['oath_type'] === null) {
                return;
            }

            $guessed_player_id = $guess['guess_player_id'];
            $guessed_player = PlayerState::load($guessed_player_id);
            $guessed_oath_type = $guess['oath_type'];

            $guessed_player_is_in_blood_oath = isset($oath_data['pairs'][$guessed_player_id]);
            $guessed_player_is_in_solitude = collect($oath_data['lone_wolves'])->contains($guessed_player_id);

            if ($guessed_oath_type === 'blood_oath') {
                if ($guessed_player_is_in_blood_oath) {
                    $player->addToScoreHistory(1, '🤔 Correctly guessed that '. $guessed_player->name. ' is in a blood oath', true);
                    $guessed_player->addToScoreHistory(-1, '🎯 ' . $player->name. ' guessed that you are in a blood oath', true);
                } else {
                    $player->addToScoreHistory(0, '🤔 Incorrectly guessed that '. $guessed_player->name. ' is in a blood oath', true);
                }
            }

            if ($guessed_oath_type === 'oath_of_solitude') {
                if ($guessed_player_is_in_solitude) {
                    $player->addToScoreHistory(1, '🤔 Correctly guessed that '. $guessed_player->name. ' is in an oath of solitude', true);
                    $guessed_player->addToScoreHistory(-1, '🎯 ' . $player->name.' guessed that you are in an oath of solitude', true);
                } else {
                    $player->addToScoreHistory(0, '🤔 Incorrectly guessed that '. $guessed_player->name. ' is in an oath of solitude', true);
                }
            }
        });
    }
}
