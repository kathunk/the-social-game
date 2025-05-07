<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\PlayerState;
use App\States\ModifierState;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasActiveGame;
use App\Events\Traits\HasActivePlayer;

class PlayerAssignedSecretAllyInTeamGame extends Event
{
    use HasActivePlayer, HasActiveGame, HasModifier;

    public int $ally_id;

    public function validate()
    {
        $this->assert(
            !isset($this->modifier->modifier_data['ally_pair_ids'][$this->player_id]),
            'Player already has an ally'
        );

        $this->assert(
            !isset($this->modifier->modifier_data['ally_pair_ids'][$this->ally_id]),
            'Ally already has an ally'
        );

        $this->assert(
            PlayerState::load($this->ally_id)->game_id === $this->game_id,
            'Ally is not in the game'
        );

        $this->assert(
            PlayerState::load($this->ally_id)->status === 'active',
            'Ally is not active'
        );
    }

    public function apply(ModifierState $modifier)
    {
        $modifier->modifier_data['ally_pair_ids'][$this->player_id] = $this->ally_id;
        $modifier->modifier_data['ally_pair_ids'][$this->ally_id] = $this->player_id;
    }

    public function handle()
    {
        $this->modifier()->updateModelWithStateData();
    }
}
