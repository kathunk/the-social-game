<?php

namespace App\Modifiers\Classes;

use App\Models\Player;

class FarmTeams extends BaseModifierClass
{
    const NAME = 'Farm Teams';

    const DESCRIPTION = 'The teams for the farm game.';

    const TYPE = 'team';

    public static function key(): string
    {
        return 'farm_teams';
    }

    public function isInvalidForTemplate(
        array $challenges,
        array $modifiers,
        string $type,
        array $team_names
    ) {
        if (! in_array(FarmMap::key(), $modifiers)) {
            return 'Farm map modifier is required to run this modifier';
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
            ->title('if not on a team')
            ->subtitle('create team')
            ->subtitle('request to join team')
            ->title('if on a team')
            ->subtitle('leave team')
            ->subtitle('propose to boot a player')
            ->build();
    }
}
