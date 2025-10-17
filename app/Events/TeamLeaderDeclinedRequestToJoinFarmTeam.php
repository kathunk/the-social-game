<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasTeam;
use App\Modifiers\Classes\FarmTeams;
use App\States\ModifierState;
use Thunk\Verbs\Event;

class TeamLeaderDeclinedRequestToJoinFarmTeam extends Event
{
    use HasGame, HasModifier, HasPlayer, HasTeam;

    public int $requester_id;

    public function validate()
    {
        $modifier = $this->state(ModifierState::class);
        $leaders = $modifier->modifier_data['leaders'] ?? [];

        // Player was leader of team
        $this->assert(
            isset($leaders[$this->team_id]) && $leaders[$this->team_id] === $this->player_id,
            'Player is not the leader of this team',
        );

        // Requester has an existing request to join team
        $requests = $modifier->modifier_data['requests'] ?? [];
        $this->assert(
            isset($requests[$this->requester_id]) && $requests[$this->requester_id] === $this->team_id,
            'Requester does not have a pending request to join this team',
        );
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
