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
        return [
            'submissions' => [],
        ];
    }
}
