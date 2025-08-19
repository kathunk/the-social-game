<?php

namespace App\Modifiers\Classes;

class TierListModifier extends BaseModifierClass
{
    const NAME = 'Tier lists';

    const TYPE = 'individual';

    public function isInvalidForTemplate(
        array $challenges,
        array $modifiers,
        string $type,
        array $team_names
    ) {
        // @todo require tier list challenges
        return false;
    }

    public static function key(): string
    {
        return 'tier_list_modifier';
    }

    public function dataArrayForState(): array
    {
        $player_ids = $this->modifier_state->game()->player_ids;

        $round_3_data = $player_ids->count() === 2
            ? ['single_opponent_round_3' => $player_ids->mapWithKeys(fn ($player_id) => [
                $player_id => [
                    'opponent' => null,
                    'A' => null,
                    'B' => null,
                    'C' => null,
                    'D' => null,
                    'F' => null,
                ],
            ])->toArray()]
            : ['single_category' => $player_ids->mapWithKeys(fn ($player_id) => [
                $player_id => [
                    'category' => null,
                    'A' => null,
                    'B' => null,
                    'C' => null,
                    'D' => null,
                    'F' => null,
                ],
            ])->toArray()];

        return [
            'submissions' => [],
            'answer_keys' => array_merge([
                'single_opponent_round_1' => $player_ids->mapWithKeys(fn ($player_id) => [
                    $player_id => [
                        'opponent' => null,
                        'A' => null,
                        'B' => null,
                        'C' => null,
                        'D' => null,
                        'F' => null,
                    ],
                ])->toArray(),
                'single_opponent_round_2' => $player_ids->mapWithKeys(fn ($player_id) => [
                    $player_id => [
                        'opponent' => null,
                        'A' => null,
                        'B' => null,
                        'C' => null,
                        'D' => null,
                        'F' => null,
                    ],
                ])->toArray(),
            ], $round_3_data),
        ];
    }
}
