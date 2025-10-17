<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasTeam;
use App\States\ModifierState;
use Thunk\Verbs\Event;

class PlayerRequestedToJoinFarmTeam extends Event
{
    use HasGame, HasModifier, HasPlayer, HasTeam;

    public function validate()
    {
        //
    }

    public function apply(ModifierState $modifier)
    {
        $modifier->modifier_data['requests'][$this->player_id] = $this->team_id;
    }

    public function handle()
    {
        $this->modifier()->updateModelWithStateData();
    }
}
