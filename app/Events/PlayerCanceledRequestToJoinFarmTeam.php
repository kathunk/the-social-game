<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\ModifierState;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasModifier;

class PlayerCanceledRequestToJoinFarmTeam extends Event
{
    use HasGame, HasPlayer, HasModifier;

    public function validate()
    {
        //
    }

    public function apply(ModifierState $modifier)
    {
        $modifier->modifier_data['requests'] = collect($modifier->modifier_data['requests'])
            ->reject(fn ($team_id, $player_id) => $player_id === $this->player_id)->toArray();
    }

    public function handle()
    {
        $this->modifier()->updateModelWithStateData();
    }
}
