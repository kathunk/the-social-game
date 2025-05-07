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
        if (! $player->team) {
            return [];
        }

        return $this->form()
            ->title('Had enough?')
            ->subtitle('You can resign at any time.')
            ->select(
                label: 'How many points should we give your team?',
                placeholder: 'Select a number...',
                options: [
                    '3' => '+3',
                    '-3' => '-3',
                ],
                property_name: 'points',
                validation_rules: 'required|string|in:' . "['-3', '3']",
                validation_messages: [
                    'points.required' => 'Please select a number.',
                    'points.string' => 'Please select a number.',
                    'in' => 'Please select between -3 and 3.',
                ],
            )
            ->buttonGroup()
            ->button(
                label: 'Resign',
                action: 'resign',
                properties_to_validate: ['points'],
            )
            ->endGroup()
            ->build();
    }

    public function resign(Player $player, array $params)
    {
        // @todo replace this when we finish validation logic for modifiers
        if(
            ! isset($params['points'])
            || (
                $params['points'] === ''
            )
        ) {
            return back()->withErrors([
                // this will not show up in the UI because we are not validating yet
                'points' => 'Points are required.',
            ]);
        }

        PlayerResignedInTeamGame::fire(
            player_id: $player->id,
            points: (int) $params['points'],
            game_id: $player->game_id,
            team_id: $player->team_id,
        );
    }
}
