<?php

namespace App\Events\Farm;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasPlayer;
use App\States\ModifierState;
use Thunk\Verbs\Event;

class PlayerCanceledRequestToJoinFarmTeam extends Event
{
    use HasGame, HasModifier, HasPlayer;

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
