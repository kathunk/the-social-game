<?php

namespace App\Modifiers\Classes\PeckingOrder;

use App\Modifiers\Classes\BaseModifierClass;
use App\Events\PeckingOrder\PlayerResignedInIndividualGame;
use App\Models\Player;

class IndividualResignation extends BaseModifierClass
{
    const NAME = 'Resignation';

    const DESCRIPTION = 'You may resign at any time. When you do, you will give your points (and hidden points) to a player your choose.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_resignation';
    }

    public function frontendComponent(Player $player): array
    {
        $points = $player->score;
        $hidden = $player->hidden_score - $player->score;
        $players = $player->game->players
            ->reject(fn ($p) => $p->id === $player->id)
            ->filter(fn ($p) => $p->status === 'active');

        return $this->form()
            ->title('Had enough?')
            ->subtitle('You can resign at any time.')
            ->select(
                property_name: 'points_beneficiary_id',
                options: $players
                    ->mapWithKeys(fn ($p) => [$p->id => $p->name])
                    ->toArray(),
                label: 'Who will you give your '.$points.' points to?',
                placeholder: 'Select a player...',
                validation_rules: 'required|in:'.
                    implode(',', $players->pluck('id')->toArray()),
                validation_messages: [
                    'required' => 'Must select a player',
                    'in' => 'Must select a valid player',
                ]
            )
            ->select(
                property_name: 'hidden_points_beneficiary_id',
                options: $players
                    ->mapWithKeys(fn ($p) => [$p->id => $p->name])
                    ->toArray(),
                label: 'Who will you give your '.
                    $hidden.
                    ' hidden points to?',
                placeholder: 'Select a player...',
                validation_rules: 'required|in:'.
                    implode(',', $players->pluck('id')->toArray()),
                validation_messages: [
                    'required' => 'Must select a player',
                    'in' => 'Must select a valid player',
                ]
            )
            ->buttonGroup()
            ->button(
                label: 'Resign',
                action: 'resign',
                properties_to_validate: [
                    'points_beneficiary_id',
                    'hidden_points_beneficiary_id',
                ]
            )
            ->endGroup()
            ->build();
    }

    public function resign(Player $player, array $params)
    {
        PlayerResignedInIndividualGame::fire(
            player_id: $player->id,
            points_beneficiary_id: (int) $params['points_beneficiary_id'],
            hidden_points_beneficiary_id: (int) $params[
                'hidden_points_beneficiary_id'
            ],
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            points: $player->score,
            hidden_points: $player->hidden_score - $player->score
        );
    }
}
