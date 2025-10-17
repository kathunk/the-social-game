<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasTeam;
use App\States\ModifierState;
use Thunk\Verbs\Event;

class TeamLeaderDeclineRequestToJoinFarmTeam extends Event
{
    use HasGame, HasModifier, HasPlayer, HasTeam;

    public int $requester_id;

    public function validate()
    {
        //
    }

    public function apply(ModifierState $modifier)
    {
        $modifier->modifier_data['requests'] = collect($modifier->modifier_data['requests'])
            ->reject(fn ($t_id, $p_id) => $p_id === $this->requester_id)->toArray();
    }

    public function handle()
    {
        $this->modifier()->updateModelWithStateData();
    }
}
