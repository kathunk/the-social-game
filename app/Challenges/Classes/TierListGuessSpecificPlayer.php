<?php

namespace App\Challenges\Classes;

use App\Models\Player;
use Illuminate\Support\Str;
use Thunk\Verbs\Facades\Verbs;
use App\Events\TierListSubmitted;
use App\Modifiers\Classes\TierListModifier;

class TierListGuessSpecificPlayer extends BaseChallengeClass
{
    const NAME = 'Mix and match';

    const DESCRIPTION = 'Below are 5 items submitted by {player_name}. But the items are mixed up from different categories. 
        Put them in the tiers that {player_name} originally intended.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'tier_list_guess_specific_player';
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
        $player_ids = $this->challenge->game->players->pluck('id')->shuffle()->toArray();
        $assignments = [];
        
        for ($i = 0; $i < count($player_ids); $i++) {
            $current_player = $player_ids[$i];
            $next_player = $player_ids[($i + 1) % count($player_ids)];
            $assignments[$current_player] = $next_player;
        }

        $player_ids->map(function ($player_id) use ($assignments) {
            $submissions_from_assigned_player = $this->submissionsForPlayer($assignments[$player_id]);

            $category_0_clues = collect($submissions_from_assigned_player)->filter(fn($submission) => $submission['category'] === 'candy')->pluck('value')->toArray();

            $clues = collect($submissions_from_assigned_player)->map(fn($submission) => [
                'category' => $submission['category'],
                'tier' => $submission['tier'],
            ]);
        });

        return [
            'has_submitted' => [],
            'assignments' => $assignments,
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $submitted = $this->categoriesSubmitted($player);
        $all_categories = $this->challenge->challenge_data['categories'];

        $next_category = collect($all_categories)->filter(fn($cat) => ! in_array($cat, $submitted))->first();

        return $this->form()
            ->title(self::NAME)
            ->when($next_category === null, fn ($form) => 
                $form
                ->subtitle("Waiting for other players to submit their lists...")
            )
            ->when($next_category !== null, fn ($form) => 
                $form
                ->subtitle(self::DESCRIPTION)
                ->divider()
                ->title(Str::title(Str::plural(Str::replace('_', ' ', $next_category))))
                ->input(
                    property_name: $next_category . '-A',
                    validation_rules: 'required|string|min:1',
                    validation_messages: ['required' => 'Submisions are required'],
                    placeholder: 'A tier ' . Str::singular(Str::replace('_', ' ', $next_category)),
                )
                ->input(
                    property_name: $next_category . '-B',
                    validation_rules: 'required|string|min:1',
                    validation_messages: ['required' => 'Submisions are required'],
                    placeholder: 'B tier ' . Str::singular(Str::replace('_', ' ', $next_category)),
                )
                ->input(
                    property_name: $next_category . '-C',
                    validation_rules: 'required|string|min:1',
                    validation_messages: ['required' => 'Submisions are required'],
                    placeholder: 'C tier ' . Str::singular(Str::replace('_', ' ', $next_category)),
                )
                ->input(
                    property_name: $next_category . '-D',
                    validation_rules: 'required|string|min:1',
                    validation_messages: ['required' => 'Submisions are required'],
                    placeholder: 'D tier ' . Str::singular(Str::replace('_', ' ', $next_category)),
                )
                ->input(
                    property_name: $next_category . '-F',
                    validation_rules: 'required|string|min:1',
                    validation_messages: ['required' => 'Submisions are required'],
                    placeholder: 'F tier ' . Str::singular(Str::replace('_', ' ', $next_category)),
                )
                ->buttonGroup()
                ->button(
                    label: 'Submit',
                    action: 'submitTierList',
                    properties_to_validate: [$next_category . '-A', $next_category . '-B', $next_category . '-C', $next_category . '-D', $next_category . '-F'],
                )
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
