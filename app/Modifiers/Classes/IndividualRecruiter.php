<?php

namespace App\Modifiers\Classes;

use App\Events\PlayerGaveReferralBonus;
use App\Models\Game;
use App\Models\Player;

class IndividualRecruiter extends BaseModifierClass
{
    const NAME = 'Pyramid Scheme';

    const DESCRIPTION = 'When you join the game, you may give a hidden point to another player for referring you.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_recruiter';
    }

    public function dataArrayForState(?Game $game = null): array
    {
        return [
            'referree_ids' => [],
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $has_referred = in_array($player->id, $this->modifier->modifier_data['referree_ids']);

        if ($has_referred) {
            return [];
        }

        $players = $player->game->players
            ->reject(fn ($p) => $p->id === $player->id)
            ->filter(fn ($p) => $p->status === 'active');

        return $this->form()
            ->title('Pyramid Scheme')
            ->subtitle('When you join the game, you may give a hidden point to another player for referring you.')
            ->select(
                property_name: 'beneficiary_id',
                options: $players->mapWithKeys(fn ($p) => [(string) $p->id => $p->name])->toArray(),
                label: 'Who will you give a hidden point to?',
                placeholder: 'Select a player...',
                validation_rules: 'required|in:'.implode(',', $players->pluck('id')->toArray()),
                validation_messages: [
                    'required' => 'Must select a player',
                    'in' => 'Must select a valid player',
                ],
            )
            ->buttonGroup()
            ->button('Give referral bonus', 'give_referral_bonus')
            ->endGroup()
            ->build();
    }

    public function give_referral_bonus(Player $player, array $params)
    {
        PlayerGaveReferralBonus::fire(
            player_id: $player->id,
            points: 0,
            hidden_points: 1,
            beneficiary_id: (int) $params['beneficiary_id'],
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
        );

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }
}
