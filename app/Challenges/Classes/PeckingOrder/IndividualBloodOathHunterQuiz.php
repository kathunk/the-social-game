<?php

namespace App\Challenges\Classes\PeckingOrder;

use App\Challenges\Classes\BaseChallengeClass;
use App\Challenges\Support\PeckingOrder\SupportsPeckingOrderBallots;
use App\Challenges\Support\PeckingOrder\HasPeckingOrderBallots;
use App\Events\PeckingOrder\PlayerSubmittedQuizGuess;
use App\Models\Player;
use App\Modifiers\Classes\PeckingOrder\BloodOaths;
use App\States\ChallengeState;
use App\States\GameState;
use App\States\PlayerState;

class IndividualBloodOathHunterQuiz extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Blood Oath Hunter';

    const DESCRIPTION = 'Select two players, who you think are in a blood oath together. If you are correct, you will gain one hidden point, and they will each receive -1 hidden point.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_blood_oath_hunter';
    }

    public function isInvalidForTemplate(
        array $challenge_keys,
        array $modifier_keys,
        string $type,
        array $team_names
    ) {
        if (! in_array(BloodOaths::key(), $modifier_keys)) {
            return 'Blood Oaths modifier is required to run this challenge';
        }

        return false;
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
        $has_guessed =
            isset($this->challenge->challenge_data['quiz_submissions'][$player->id])
            && $this->challenge->challenge_data['quiz_submissions'][$player->id]['guess_player_ids'] !== null;

        $quiz_description = $has_guessed
            ? '🤔 Guessed that '.
                Player::find($this->challenge->challenge_data['quiz_submissions'][$player->id]['guess_player_ids'][0])->name.
                ' and '.
                Player::find($this->challenge->challenge_data['quiz_submissions'][$player->id]['guess_player_ids'][1])->name.
                ' are in a blood oath.'
            : null;

        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->when(
                $has_guessed,
                fn ($form) => $form->subtitle($quiz_description)
            )
            ->when(
                ! $has_guessed,
                fn ($form) => $form
                    ->select(
                        property_name: 'guess_player_id',
                        options: $players
                            ->mapWithKeys(fn ($p) => [$p->id => $p->name])
                            ->toArray(),
                        label: 'Select a player',
                        placeholder: 'Select a player...',
                        validation_rules: 'required|in:'.
                            implode(',', $players->pluck('id')->toArray()),
                        validation_messages: [
                            'required' => 'Must select a player',
                            'in' => 'Must select a valid player',
                        ]
                    )
                    ->select(
                        property_name: 'guess_player_id_2',
                        options: $players
                            ->mapWithKeys(fn ($p) => [$p->id => $p->name])
                            ->toArray(),
                        label: 'Select a second player',
                        placeholder: 'Select a second player...',
                        validation_rules: 'required|in:'.
                            implode(',', $players->pluck('id')->toArray()),
                        validation_messages: [
                            'required' => 'Must select a second player',
                            'in' => 'Must select a valid second player',
                        ]
                    )
                    ->buttonGroup()
                    ->button(
                        label: 'Submit Guess',
                        action: 'guess',
                        properties_to_validate: [
                            'guess_player_id',
                            'guess_player_id_2',
                        ]
                    )
                    ->endGroup()
            )
            ->when(! $has_guessed || ! $this->hasVoted($player), fn ($form) => $form->divider())
            ->when(
                $this->hasVoted($player),
                fn ($form) => $form->subtitle($this->voteDescription($player))
            )
            ->when(
                ! $this->hasVoted($player),
                fn ($form) => $form->peckingOrderBallot(
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
                'guess_player_ids' => [
                    $params['guess_player_id'],
                    $params['guess_player_id_2'],
                ],
            ]
        );
    }

    public function onChallengeEnded(GameState $game_state)
    {
        $this->applyVotesToScore($game_state);

        $this->challenge_state = ChallengeState::load(
            $this->challenge_state->id
        );

        $oath_data = $game_state
            ->modifiers()
            ->firstWhere('class_key', BloodOaths::key())->modifier_data;

        $game_state->players()->each(function ($player) use ($oath_data) {
            if (! isset($this->challenge_state->challenge_data['quiz_submissions'][$player->id])) {
                return;
            }

            $guess =
                $this->challenge_state->challenge_data['quiz_submissions'][
                    $player->id
                ];

            if ($guess['guess_player_ids'] === null) {
                return;
            }

            $guessed_player_ids = collect($guess['guess_player_ids'])
                ->map(fn ($id) => (int) $id)
                ->toArray();
            $guessed_player_1 = PlayerState::load($guessed_player_ids[0]);
            $guessed_player_2 = PlayerState::load($guessed_player_ids[1]);

            $guessed_players_are_in_blood_oath =
                isset($oath_data['pairs'][$guessed_player_ids[0]]) &&
                $oath_data['pairs'][$guessed_player_ids[0]] ===
                    $guessed_player_ids[1];

            if ($guessed_players_are_in_blood_oath) {
                $player->addToScoreHistory(
                    icon: '🤔',
                    points: 1,
                    description: 'Correctly guessed that '.
                        $guessed_player_1->name.
                        ' and '.
                        $guessed_player_2->name.
                        ' are in a blood oath',
                    is_hidden: true,
                );
                $guessed_player_1->addToScoreHistory(
                    icon: '🎯',
                    points: -1,
                    description: $player->name.' guessed that you are in a blood oath',
                    is_hidden: true,
                );
                $guessed_player_2->addToScoreHistory(
                    icon: '🎯',
                    points: -1,
                    description: $player->name.' guessed that you are in a blood oath',
                    is_hidden: true,
                );
            } else {
                $player->addToScoreHistory(
                    icon: '🤔',
                    points: 0,
                    description: 'Incorrectly guessed that '.
                        $guessed_player_1->name.
                        ' and '.
                        $guessed_player_2->name.
                        ' are in a blood oath',
                    is_hidden: true,
                );
            }
        });
    }
}
