<?php

namespace App\Events\Farm;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasTeam;
use App\States\GameState;
use App\States\ModifierState;
use Thunk\Verbs\Event;

class TeamLeaderAcceptedRequestToJoinFarmTeam extends Event
{
    use HasGame, HasModifier, HasPlayer, HasTeam;

    public int $requester_id;

    public function validate()
    {
        $game = $this->state(GameState::class);
        $teams_modifier = $game->modifiers()->firstWhere('class_key', \App\Modifiers\Farm\FarmTeams::key());

        // Player was leader of team
        $team_leader_id = $teams_modifier->modifier_data['leaders'][$this->team_id] ?? null;

        $this->assert(
            $team_leader_id === $this->player_id,
            'Player is not the leader of this team',
        );

        // Requester has an existing request to join team (check this BEFORE checking if they're already on team)
        $request_team_id = $teams_modifier->modifier_data['requests'][$this->requester_id] ?? null;

        $this->assert(
            $request_team_id !== null,
            'Requester does not have a pending request',
        );

        $this->assert(
            $request_team_id === $this->team_id,
            'Requester has not requested to join this team',
        );
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
