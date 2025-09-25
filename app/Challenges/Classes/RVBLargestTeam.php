<?php

namespace App\Challenges\Classes;

use App\Models\Player;
use App\States\GameState;

class RVBLargestTeam extends BaseChallengeClass
{
    const NAME = 'Critical Mass';

    const DESCRIPTION = 'Each member of he smallest team at the end of this challenge will get 2 points.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'rvb_largest_team';
    }

    public function dataArrayForState(): array
    {
        return [

        ];
    }

    public function frontendComponent(Player $player): array
    {
    }

    public function onChallengeEnded(
        GameState $game_state,
    ) {
        
    }
}
