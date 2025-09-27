<?php

namespace App\Challenges\Classes;

use App\Models\Player;
use App\Modifiers\Classes\FarmMap;

class FarmRound extends BaseChallengeClass
{
    const NAME = 'Farm round';

    const DESCRIPTION = 'description';

    const TYPE = 'team';

    public static function key(): string
    {
        return 'farm_round';
    }

    public function isInvalidForTemplate(
        array $challenge_keys,
        array $modifier_keys,
        string $type,
        array $team_names
    ) {
        if (! in_array(FarmMap::key(), $modifier_keys)) {
            return 'Farm map modifier is required to run this challenge';
        }

        return false;
    }

    public function dataArrayForState(): array
    {
        return [];
    }

    public function frontendComponent(Player $player): array
    {
        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->build();
    }
}
