<?php

namespace App\Challenges\Classes;

use App\Models\Player;
use Illuminate\Support\Str;
use Thunk\Verbs\Facades\Verbs;
use App\Events\TierListsConstructed;
use App\Modifiers\Classes\TierListModifier;

class TierListConstructionPhase extends BaseChallengeClass
{
    const NAME = 'Build your tier lists';

    const DESCRIPTION = 'Add a submission for each tier in each category below.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'tier_list_construction_phase';
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
        // @todo prioritize categories that have been used less often
        // $previously_played_categories = $this->game->players

        $categories = collect([
            'Candies',
            'Cities',
            'Fictional characters',
            'Movies',
            'School subjects',
            'TV Shows',
        ])
        ->shuffle()
        ->take(3)
        ->toArray();

        return [
            'has_submitted' => [],
            'categories' => $categories,
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $has_submitted = in_array($player->id, $this->challenge->challenge_data['has_submitted']);

        $categories = $this->challenge->challenge_data['categories'];

        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->when(! $has_submitted, fn ($form) => 
                $form
                ->tierListInputs($categories[0])
                ->tierListInputs($categories[1])
                ->tierListInputs($categories[2])
                ->buttonGroup()
                ->button(
                    label: 'Submit Tier Lists',
                    action: 'submitTierList',
                    properties_to_validate: [],
                )
                ->endGroup()
            )
            ->build();
    }

    public function submitTierList(Player $player, array $params)
    {
        $mapped = [];

        $mapped = collect($params)->mapWithKeys(fn($entry, $key) =>
            [
                'value' => $entry,
                'category' => Str::before($key, '-'),
                'tier' => Str::afterLast($key, '-'),
            ]
            );
        // ->each(fn($item) => 
        //     $mapped[] = $item
        //     // $mapped[$item['category']][$item['tier']] = $item['value']
        // );

        dd($mapped);

        TierListsConstructed::fire(
            player_id: $player->id,
            challenge_id: $this->challenge->id,
            modifier_id: $player->game->modifiers->firstWhere('class_key', TierListModifier::key())->id,
            game_id: $this->challenge->game_id,
            submissions: $params['submissions'],
        );

        Verbs::commit();

        if (count($this->challenge->fresh()->challenge_data['has_submitted']) === $player->game->players->count()) {
            $this->challenge->end();
        }
    }
}
