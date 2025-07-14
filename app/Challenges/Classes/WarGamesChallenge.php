<?php

namespace App\Challenges\Classes;

use App\Models\Player;
use App\Modifiers\Classes\WarGamesMap;

class WarGamesChallenge extends BaseChallengeClass
{
    const NAME = 'War Games';

    const DESCRIPTION = 'The standard challenge for War Games';

    const TYPE = 'team';

    public static function key(): string
    {
        return 'war_games';
    }

    public function isInvalidForTemplate(
        array $challenge_keys,
        array $modifier_keys,
        string $type,
        array $team_names
    ) {
        if (! in_array(WarGamesMap::key(), $modifier_keys)) {
            return 'War Games Map modifier is required to run this challenge';
        }

        return false;
    }

    public function frontendComponent(Player $player): array
    {
        return $this->form()->build();
    }
}
