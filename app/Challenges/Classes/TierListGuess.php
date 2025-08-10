<?php

namespace App\Challenges\Classes;

use App\Events\PlayerReadiedUp;
use App\Events\PlayerSubmittedTierListGuess;
use App\Models\Player;
use App\Modifiers\Classes\TierListModifier;
use Thunk\Verbs\Facades\Verbs;

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
        ];
    }

    public function answerKeysForRound(): array
    {
        $all_rounds = $this->challenge->game->challenges;
        $current_round_number = $all_rounds->search($this->challenge) + 1;

        $target_round_array_key = match ($current_round_number) {
            1 => 'single_opponent_round_1',
            2 => 'single_opponent_round_2',
            3 => 'single_category',
        };

        return $this->modifier()->modifier_data['answer_keys'][$target_round_array_key];
    }

    public function frontendComponent(Player $player): array
    {
        $answers = $this->challenge->challenge_data['assignments'][$player->id];
        $type = $this->challenge->challenge_data['type'];
        $has_submitted = in_array($player->id, $this->challenge->challenge_data['has_submitted']);
        $all_players_have_submitted = count($this->challenge->challenge_data['has_submitted']) === $this->challenge->game->players->count();

        $help_text = match ($type) {
            'opponent' => 'Below are 5 items submitted by '.$answers['opponent'].', in no particular order. Drag and drop the items from best to worst.',
            'category' => 'Below are 5 items submitted for '.$answers['category'].', in no particular order. Drag and drop the items from best to worst.',
        };

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
                ->poll(5000)
                ->when($all_players_have_submitted, fn ($form) => $form
                    ->buttonGroup()
                    ->button('Continue', 'readyUp')
                    ->endGroup()
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
        }

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }
}
