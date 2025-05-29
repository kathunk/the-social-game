<?php

namespace App\Modifiers\Classes;

use App\Models\Player;
use Illuminate\Support\Collection;
use App\Events\PlayerOfferedBloodOath;
use App\Events\PlayerMadeOathOfSolitude;

class BloodOaths extends BaseModifierClass
{
    const NAME = 'Blood Oaths';

    const DESCRIPTION = 'Create an unbreakable alliance with another player, and play the game together in secret. 
        Or, gain hidden points for making an oath of solitude.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'blood_oaths';
    }

    public function dataArrayForState(): array
    {
        return ['offers' => [], 'pairs' => [], 'lone_wolves' => []];
    }

    public function frontendComponent(Player $player): array
    {
        $data = $this->modifier->modifier_data;

        if (in_array($player->id, $data['lone_wolves'])) {
            return $this->form()
                ->title('You have made an oath of solitude')
                ->build();
        }

        $partner_id = $data['pairs'][$player->id] ?? null;
        $partner = Player::find($partner_id);

        if ($partner) {
            return $this->form()
                ->title('You are in a blood oath with '.$partner->name)
                ->build();
        }

        $players = $player->game->players
            ->reject(fn ($p) => $p->id === $player->id);

        $offer_id = $data['offers'][$player->id] ?? null;
        $offer = Player::find($offer_id);

        $incoming_offers = collect($data['offers'])->filter(fn ($id) => $id === $player->id);
        $incoming_offers_players = Player::whereIn('id', $incoming_offers->keys())->get();

        $players = $players->reject(fn ($p) => $p->id === $offer_id);

        return $this->form()
            ->title('Offer a blood oath')
            ->subtitle('If you offer an oath to a partner who offers you one,
                your scores will be combined at the end of the game, and you will win or lose as a pair.')
            ->when($offer !== null, function ($form) use ($offer) {
                return $form->subtitle('Your current offer: '.$offer->name.' (you may change this at any time)');
            })
            ->when($incoming_offers_players->count() > 0, function ($form) use ($incoming_offers_players) {
                return $form->subtitle('Players currently offering you an oath: '.$incoming_offers_players->pluck('name')->implode(', '));
            })
            ->select(
                label: 'Select a player',
                property_name: 'oath_offer_id',
                options: $players->mapWithKeys(fn ($p) => [$p->id => $p->name])->toArray(),
                placeholder: 'Select a player...',
                validation_rules: 'required|in:'.implode(',', $players->pluck('id')->toArray()),
                validation_messages: [
                    'required' => 'Must select a player',
                    'in' => 'Must select a valid player',
                ],
            )
            ->buttonGroup()
            ->button(
                label: 'Offer Oath',
                action: 'offer_blood_oath',
                properties_to_validate: ['oath_offer_id'],
            )
            ->endGroup()
            ->divider()
            ->title('Or: Declare an oath of solitude')
            ->subtitle('Declaring an oath of solitude means you cannot be in an alliance with anyone else.
                For your bravery, you will gain 3 hidden points.')
            ->buttonGroup()
            ->button(
                label: 'Declare Oath of Solitude',
                action: 'declare_oath_of_solitude',
                properties_to_validate: [],
            )
            ->endGroup()
            ->build();
    }

    public function offer_blood_oath(Player $player, array $params)
    {
        PlayerOfferedBloodOath::fire(
            player_id: $player->id,
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            oath_offer_id: (int) $params['oath_offer_id'],
        );
    }

    public function declare_oath_of_solitude(Player $player, array $params)
    {
        PlayerMadeOathOfSolitude::fire(
            player_id: $player->id,
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
        );
    }
}
