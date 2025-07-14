<?php

namespace App\Modifiers\Classes;

use App\Models\Player;

class WarGamesMap extends BaseModifierClass
{
    const NAME = 'War Games Map';

    const DESCRIPTION = 'A map of the War Games';

    const TYPE = 'team';

    public static function key(): string
    {
        return 'war_games_map';
    }

    public function frontendComponent(Player $player): array
    {
        return $this->form()
            ->title('War Games Map')
            ->subtitle('map :)')
            ->warGameMap()
            ->build();
    }
}
