<?php

namespace App\Events\Farm;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasTeam;
use App\States\GameState;
use App\States\ModifierState;
use Thunk\Verbs\Event;

class PlayerRequestedToJoinFarmTeam extends Event
{
    use HasGame, HasModifier, HasPlayer, HasTeam;

    public function validate()
    {
        $game = $this->state(GameState::class);
        $teams_modifier = $game->modifiers()->firstWhere('class_key', \App\Modifiers\Classes\Farm\FarmTeams::key());

        // Player was not already on team
        $player = $this->state(\App\States\PlayerState::class);
        $this->assert(
            $player->team_id !== $this->team_id,
            'Player is already on this team',
        );

        // Player is on space with team leader
        $map_state = $game->modifiers()->firstWhere('class_key', \App\Modifiers\Classes\Farm\FarmMap::key());
        $player_space = collect($map_state->modifier_data)
            ->firstWhere(fn ($space) => in_array($this->player_id, $space['player_ids']));

        $this->assert(
            $player_space !== null,
            'Player is not currently on any space',
        );

        // Get team leader for the target team
        $team_leader_id = $teams_modifier->modifier_data['leaders'][$this->team_id] ?? null;

        $this->assert(
            $team_leader_id !== null,
            'Team does not have a leader',
        );

        $this->assert(
            in_array($team_leader_id, $player_space['player_ids']),
            'Player is not on the same space as the team leader',
        );
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
