<?php

namespace App\Modifiers\Laracon2025;

use App\Modifiers\BaseModifierClass;
use App\Events\PlayerResignedInTeamGame;
use App\Models\Player;

class TeamResignation extends BaseModifierClass
{
    const NAME = 'Resignation';

    const DESCRIPTION = 'Players can resign at any time. When they do, they may give their team +3 or -3 points.';

    const TYPE = 'team';

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
                validation_rules: 'required|string|in:3,-3',
                validation_messages: [
                    'required' => 'Please select a number.',
                    'string' => 'Please select a number.',
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
        PlayerResignedInTeamGame::fire(
            player_id: $player->id,
            points: (int) $params['points'],
            game_id: $player->game_id,
            team_id: $player->team_id,
        );

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }
}
