<?php

namespace App\Modifiers\Classes;

class TierListModifier extends BaseModifierClass
{
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

        return [
            'submissions' => [],
            'answer_keys' => [
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
                'single_category' => $player_ids->mapWithKeys(fn ($player_id) => [
                    $player_id => [
                        'category' => null,
                        'A' => null,
                        'B' => null,
                        'C' => null,
                        'D' => null,
                        'F' => null,
                    ],
                ])->toArray(),
            ],
        ];
    }
}
