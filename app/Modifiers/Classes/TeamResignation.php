<?php

namespace App\Modifiers\Classes;

use App\Events\PlayerResignedInTeamGame;
use App\Models\Player;

class TeamResignation extends BaseModifierClass
{
    const NAME = 'Resignation';

    const DESCRIPTION = 'Players can resign at any time. When they do, they may give their team +3 or -3 points.';

    const TYPE = 'team'; // team or individual

    public static function key(): string
    {
        return 'team_resignation';
    }

    public function frontendComponent(Player $player): array
    {
        // @todo implement
        $player_has_resigned = false;

        if (! $player->team) {
            return [];
        }

        return $this->form()
            ->title('Had enough?')
            ->subtitle('You can resign at any time.')
            ->select(
                label: 'How many points should we give your team?',
                options: [
                    // @todo I'm still running into this issue where the first option in the select looks real but has no value
                    3 => '+3',
                    -3 => '-3',
                ],
                property_name: 'points',
                validation_rules: 'required|integer|min:-3|max:3',
                validation_messages: [
                    'points.required' => 'Please select a number.',
                    'points.integer' => 'Please select a number.',
                    'points.min' => 'Please select a number between -3 and 3.',
                    'points.max' => 'Please select a number between -3 and 3.',
                ],
            )
            ->buttonGroup()
            ->button(
                label: 'Resign',
                action: 'resign',
            )
            ->endGroup()
            ->build();
    }

    public function resign(Player $player, array $params)
    {
        $points = $params['points'];

        PlayerResignedInTeamGame::fire(
            player_id: $player->id,
            points: (int) $points,
            game_id: $player->game_id,
            team_id: $player->team_id,
        );
    }
}
