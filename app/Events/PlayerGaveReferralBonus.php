<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasPlayer;
use App\Models\Modifier;
use App\Models\Player;
use App\States\GameState;
use App\States\ModifierState;
use App\States\PlayerState;
use Thunk\Verbs\Event;

class PlayerGaveReferralBonus extends Event
{
    use HasGame, HasModifier, HasPlayer;

    public int $beneficiary_id;

    public int $points;

    public int $hidden_points;

    public function validate()
    {
        $beneficiary = PlayerState::load($this->beneficiary_id);

        $this->assert(
            $this->state(GameState::class)->player_ids->contains($this->beneficiary_id),
            'Beneficiary is not in the game'
        );

        $this->assert(
            $beneficiary->game_id === $this->game_id,
            'Beneficiary is not in the game'
        );

        $this->assert(
            $beneficiary->status === 'active',
            'Beneficiary must be active'
        );
    }

    public function apply(GameState $game)
    {
        $beneficiary = PlayerState::load($this->beneficiary_id);

        if ($this->hidden_points > 0) {
            $beneficiary->addToScoreHistory(
                icon: '🎁',
                points: $this->hidden_points,
                description: 'Referral bonus from '.$this->state(PlayerState::class)->name,
                is_hidden: true,
            );
        }

        if ($this->points > 0) {
            $beneficiary->addToScoreHistory(
                icon: '🎁',
                points: $this->points,
                description: 'Referral bonus from '.$this->state(PlayerState::class)->name,
            );
        }
    }

    public function applyToModifier(ModifierState $modifier)
    {
        $modifier->modifier_data['referree_ids'][] = $this->player_id;
    }

    public function handle()
    {
        $beneficiary = Player::find($this->beneficiary_id);
        $beneficiary->updateModelWithStateData();
        $modifier = Modifier::find($this->modifier_id);
        $modifier->updateModelWithStateData();
    }
}
