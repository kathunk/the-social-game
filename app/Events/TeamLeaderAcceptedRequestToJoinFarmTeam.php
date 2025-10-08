<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\ModifierState;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasTeam;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasModifier;

class TeamLeaderAcceptedRequestToJoinFarmTeam extends Event
{
    use HasGame, HasPlayer, HasTeam, HasModifier;

    public int $requester_id;

    public function validate()
    {
        //
    }

    public function apply(ModifierState $modifier)
    {
        $modifier->modifier_data['requests'] = collect($modifier->modifier_data['requests'])
            ->reject(fn ($team_id, $player_id) => $player_id === $this->requester_id)->toArray();
    }

    public function handle()
    {
        $this->modifier()->updateModelWithStateData();
    }
}
