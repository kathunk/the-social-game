<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\ModifierState;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasTeam;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasModifier;

class PlayerRequestedToJoinFarmTeam extends Event
{
    use HasGame, HasPlayer, HasTeam, HasModifier;

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
