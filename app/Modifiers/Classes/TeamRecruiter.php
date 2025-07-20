<?php

namespace App\Modifiers\Classes;

use App\Models\Player;
use App\States\GameState;
use App\States\ModifierState;
use App\States\PlayerState;
use App\States\TeamState;

class TeamRecruiter extends BaseModifierClass
{
    const NAME = 'Pyramid Scheme';

    const DESCRIPTION = 'When a new player joins your team, the team will receive 2 points.';

    const TYPE = 'team';

    public static function key(): string
    {
        return 'team_recruiter';
    }

    public function frontendComponent(Player $player): array
    {
        return $this->form()
            ->title('Team Recruiter')
            ->subtitle('When a new player joins your team, the team will receive 2 points.')
            ->build();
    }

    public function onPlayerJoinedTeam(
        PlayerState $player_state,
        TeamState $team_state,
        GameState $game_state,
        ModifierState $modifier_state,
        ?TeamState $previous_team = null
    ) {
        if ($previous_team) {
            return;
        }

        $team_state->addToScoreHistory(2, '🔺 '.$player_state->name.' joined the game');
    }
}
