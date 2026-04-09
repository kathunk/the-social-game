<?php

namespace App\Challenges;

use App\Models\Player;

class TeamFiller extends BaseChallengeClass
{
    const NAME = 'Do not use';

    const DESCRIPTION = 'Do not use';

    const TYPE = 'team';

    public static function key(): string
    {
        return 'team_filler';
    }

    public function isInvalidForTemplate(array $challenge_keys, array $modifier_keys, string $type, array $team_names)
    {
        if (env('APP_ENV') === 'testing') {
            return false;
        }

        return 'The filler challenge is for test purposes only';
    }

    public function frontendComponent(Player $player): array
    {
        return $this->form()->build();
    }
}
