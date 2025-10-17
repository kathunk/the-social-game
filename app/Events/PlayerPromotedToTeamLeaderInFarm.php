<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasTeam;
use App\States\GameState;
use App\States\ModifierState;
use Thunk\Verbs\Event;

class PlayerPromotedToTeamLeaderInFarm extends Event
{
    use HasGame, HasModifier, HasPlayer, HasTeam;

    public function validate()
    {
        $game = $this->state(GameState::class);
        $teams_modifier = $game->modifiers()->firstWhere('class_key', \App\Modifiers\Classes\FarmTeams::key());
        $player = $this->state(\App\States\PlayerState::class);

        // Check if this is the initial team leader assignment (team has no leader yet)
        $team_leader_id = $teams_modifier->modifier_data['leaders'][$this->team_id] ?? null;
        $is_initial_assignment = $team_leader_id === null;

        // For initial assignment, player must either be on the team or have no team
        // For promotion, player must be on the specific team
        if ($is_initial_assignment) {
            $this->assert(
                $player->team_id === $this->team_id || $player->team_id === null,
                'Player is on a different team',
            );
        } else {
            $this->assert(
                $player->team_id === $this->team_id,
                'Player is not on the specified team',
            );
        }

        // Player was not already a team leader
        $this->assert(
            $team_leader_id !== $this->player_id,
            'Player is already the leader of this team',
        );
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
