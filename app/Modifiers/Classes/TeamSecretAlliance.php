<?php

namespace App\Modifiers\Classes;

use App\States\GameState;
use App\States\PlayerState;
use App\States\TeamState;

class TeamSecretAlliance extends BaseModifierClass
{
    const NAME = 'Secret Alliance';

    const DESCRIPTION = 'Players are randomly assigned an alliance on another team. If they move to a new team with that alliance, that team gains 10 points.';

    const TYPE = 'team'; // team or individual

    public static function key(): string
    {
        return 'team_secret_alliance';
    }

    public function onPlayerJoinedTeam(
        PlayerState $player_state,
        TeamState $team_state,
        GameState $game_state,
        ?TeamState $previous_team = null,
    ) {
        // Optional override
    }

    public function dataArrayForState(): array
    {
        return [];
    }
}
