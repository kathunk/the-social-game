<?php

namespace App\Challenges\Classes;

use App\Models\Player;
use Illuminate\Support\Str;
use Thunk\Verbs\Facades\Verbs;
use App\Events\TierListSubmitted;
use App\Modifiers\Classes\TierListModifier;

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
        $all_rounds = $this->challenge->game->challenges;
        $current_round_number = $all_rounds->search($this->challenge) + 1;
        $all_assignment_data = collect($this->modifier()->modifier_data['answer_keys']);
        $current_assignment_data = $all_assignment_data->skip($current_round_number -1)->first();
        $type = collect($all_assignment_data)->keys()->skip($current_round_number -1)->first();

        return [
            'has_submitted' => [],
            'assignments' => $current_assignment_data,
            'type' => $type,
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $answers = $this->challenge->challenge_data['assignments'][$player->id];
        $type = $this->challenge->challenge_data['type'];
        $has_submitted = in_array($player->id, $this->challenge->challenge_data['has_submitted']);

        return $this->form()
            ->title(self::NAME)
            ->subtitle('Drag and drop the items from best to worst. When you are done, click submit.')
            ->when($has_submitted, fn($form) => $form->title('Show results...'))
            ->when(!$has_submitted, fn($form) => 
                $form->tierListGuess($answers, $type)
                    ->buttonGroup()
                    ->button('Submit', 'submitTierList')
                    ->endGroup()
            )
            ->build();
    }

    public function modifier()
    {
        return $this->challenge->game->modifiers->firstWhere('class_key', TierListModifier::key());
    }

    public function submissionsForPlayer(int $player_id): array
    {
        return collect($this->modifier()->modifier_data['submissions'])
            ->filter(fn($submission) => $submission['player_id'] === $player_id)
            ->toArray();
    }

    public function categoriesSubmitted(Player $player): array
    {
        return collect($this->modifier()->modifier_data['submissions'])
            ->filter(fn($submission) => $submission['player_id'] === $player->id)
            ->pluck('category')
            ->unique()
            ->toArray();
    }

    public function submitTierList(Player $player, array $params)
    {
        dd($params);
        $mapped = collect($params)->map(fn($entry, $key) =>
            [
                'value' => $entry,
                'category' => Str::before($key, '-'),
                'tier' => Str::afterLast($key, '-'),
                'player_id' => $player->id,
                'used' => false,
            ]
        )
        ->values()
        ->toArray();

        TierListSubmitted::fire(
            player_id: $player->id,
            challenge_id: $this->challenge->id,
            modifier_id: $this->modifier()->id,
            game_id: $this->challenge->game_id,
            submissions: $mapped,
        );

        Verbs::commit();
        
        if (count($this->challenge->fresh()->challenge_data['has_submitted']) === $player->game->players->count()) {
            $this->challenge->fresh()->end();
        }

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }
}
