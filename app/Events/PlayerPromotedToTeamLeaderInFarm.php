<?php

namespace App\Events;

use App\States\ModifierState;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasTeam;
use Thunk\Verbs\Event;

class PlayerPromotedToTeamLeaderInFarm extends Event
{
    use HasPlayer, HasTeam, HasGame, HasModifier;

    public function validate()
    {
        // player is a team leader
    }

    public function apply(ModifierState $modifier)
    {
        $modifier->modifier_data['leaders'][$this->team_id] = $this->player_id;
    }

    public function handle()
    {
        $this->modifier()->updateModelWithStateData();
    }
}
