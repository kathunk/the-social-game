<?php

namespace App\Challenges\Classes;

use App\Models\Player;
use Illuminate\Support\Str;
use Thunk\Verbs\Facades\Verbs;
use App\Events\PlayerReadiedUp;
use App\Events\GameUpdatedForReverb;
use App\Modifiers\Classes\TierListModifier;
use App\Events\PlayerSubmittedTierListGuess;

class TierListGuess extends BaseChallengeClass
{
    const NAME = 'Guess the tiers';

    const DESCRIPTION = 'description';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'guess_the_tiers';
    }

    public function isInvalidForTemplate(
        array $challenge_keys,
        array $modifier_keys,
        string $type,
        array $team_names
    ) {
        if (! in_array(TierListModifier::key(), $modifier_keys)) {
            return 'Tier list modifier is required to run this challenge';
        }

        return false;
    }

    public function dataArrayForState(): array
    {
        $answer_keys = $this->answerKeysForRound();
        $type = array_key_exists('opponent', collect($answer_keys)->first()) ? 'opponent' : 'category';

        return [
            'has_submitted' => [],
            'has_readied_up' => [],
            'assignments' => $this->answerKeysForRound(),
            'type' => $type,
            'results' => [],
        ];
    }

    public function answerKeysForRound(): array
    {
        $current_round_number = $this->challenge->round_number;

        $target_round_array_key = match ($current_round_number) {
            2 => 'single_opponent_round_1',
            3 => 'single_opponent_round_2',
            4 => 'single_category',
        };

        return $this->modifier()->modifier_data['answer_keys'][$target_round_array_key];
    }

    public function frontendComponent(Player $player): array
    {
        $answers = $this->challenge->challenge_data['assignments'][$player->id];
        $type = $this->challenge->challenge_data['type'];
        $has_submitted = in_array($player->id, $this->challenge->challenge_data['has_submitted']);
        $all_players_have_submitted = count($this->challenge->challenge_data['has_submitted']) === $this->challenge->game->players->count();
        $has_readied_up = in_array($player->id, $this->challenge->challenge_data['has_readied_up']);
        $is_final_round = $this->challenge->game->challenges->where('status', 'upcoming')->count() === 0;

        $help_text = match ($type) {
            'opponent' => 'Below are 5 items submitted by '.$answers['opponent'].', in no particular order. Drag and drop the items from best to worst.',
            'category' => 'Below are 5 items submitted for '.Str::title(Str::replace('_', ' ', $answers['category'])).', in no particular order. Drag and drop the items from best to worst.',
        };

        if ($has_submitted) {
            $results = $this->challenge->fresh()->challenge_data['results'][$player->id] ?? [];

            $results_table = collect($results)->map(function ($result) {
                return [
                    'guessed_tier' => $result['guessed_tier'],
                    'correct_tier' => $result['correct_tier'],
                    'points' => $result['points'] > -1 ? '+' . $result['points'] : $result['points'],
                    'item' => $result['original_submission_value'],
                ];
            })->toArray();
        }

        return $this->form()
            ->title(self::NAME)
            ->when(! $has_submitted, fn ($form) => $form
                ->subtitle($help_text)
                ->tierListGuess($answers, $type)
                ->buttonGroup()
                ->button('Submit', 'submitTierList')
                ->endGroup()
            )
            ->when($has_submitted, fn ($form) => $form
                ->when(! $all_players_have_submitted, fn ($form) => $form
                    ->subtitle('Waiting for everyone to submit...')
                )
                ->when($all_players_have_submitted, fn ($form) => $form
                    ->table(headers: ['Guess', 'Correct Tier', 'Points', 'Item'], rows: $results_table)
                    ->when(! $has_readied_up, fn ($form) => $form
                        ->buttonGroup()
                        ->button($is_final_round ? 'End game' : 'Ready for next round', 'readyUp')
                        ->endGroup()
                    )
                    ->when($has_readied_up, fn ($form) => $form
                        ->divider()
                        ->subtitle($is_final_round ? 'Waiting for everyone to finish...' : 'Waiting for everyone to ready up...')
                    )
                )
            )
            ->build();
    }

    public function modifier()
    {
        return $this->challenge->game->modifiers->firstWhere('class_key', TierListModifier::key());
    }

    public function submitTierList(Player $player, array $params)
    {
        PlayerSubmittedTierListGuess::fire(
            player_id: $player->id,
            game_id: $player->game_id,
            challenge_id: $this->challenge->id,
            modifier_id: $this->modifier()->id,
            answer_key: $this->answerKeysForRound()[$player->id],
            guesses: $params['guesses_array'],
        );

        Verbs::commit();

        if (count($this->challenge->challenge_data['has_submitted']) === $this->challenge->game->players->count()) {
            event(new GameUpdatedForReverb($player->game->fresh()));
        }

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    public function allPlayersHaveReadiedUp(): bool
    {
        return count($this->challenge->fresh()->challenge_data['has_readied_up']) === $this->challenge->game->players->count();
    }

    public function readyUp(Player $player, array $params)
    {
        PlayerReadiedUp::fire(
            player_id: $player->id,
            game_id: $player->game_id,
            challenge_id: $this->challenge->id,
        );

        Verbs::commit();

        if ($this->allPlayersHaveReadiedUp()) {
            $this->challenge->fresh()->end();
            $this->challenge->next()
                ? $this->challenge->next()->start()
                : $this->challenge->game->end();

            Verbs::commit();
            event(new GameUpdatedForReverb($player->game->fresh()));
        }

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }
}
