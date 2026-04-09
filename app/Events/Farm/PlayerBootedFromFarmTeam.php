<?php

namespace App\Events\Farm;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasTeam;
use App\States\GameState;
use App\States\ModifierState;
use App\States\PlayerState;
use App\States\TeamState;
use Thunk\Verbs\Event;

class PlayerBootedFromFarmTeam extends Event
{
    use HasGame, HasModifier, HasPlayer, HasTeam;

    public int $booter_player_id;

    public int $grain_in_possession;

    public function validate()
    {
        $game = $this->state(GameState::class);
        $teams_modifier = $game->modifiers()->firstWhere('class_key', \App\Modifiers\Classes\Farm\FarmTeams::key());

        // Player was on team
        $player = $this->state(PlayerState::class);
        $this->assert(
            $player->team_id === $this->team_id,
            'Player is not on the specified team',
        );

        // Booter was leader of team
        $team_leader_id = $teams_modifier->modifier_data['leaders'][$this->team_id] ?? null;

        $this->assert(
            $team_leader_id === $this->booter_player_id,
            'Booter is not the leader of the team',
        );
    }

    public function applyToPlayer(PlayerState $player)
    {
        $player->team_id = null;
    }

    public function applyToModifier(ModifierState $modifier)
    {
        $modifier->modifier_data['leaders'] = collect($modifier->modifier_data['leaders'])
            ->map(function ($player_id, $team_id) {
                if ($team_id === $this->team_id) {
                    $previous_team = $this->state(TeamState::class);

                    return $previous_team->player_ids
                        ->reject(fn ($p_id) => $p_id === $this->player_id)->first();
                }

                return $player_id;
            })->toArray();
    }

    public function applyToTeam(TeamState $team)
    {
        $team->player_ids = $team->player_ids->reject(fn ($player_id) => $player_id === $this->player_id);
        $player = PlayerState::load($this->player_id);

        $amount_to_transfer = $this->grain_in_possession;

        $team->addToScoreHistory('🥾', -$amount_to_transfer, $player->name.' was booted from team and took '.$amount_to_transfer.' grain with them.');
    }

    public function handle()
    {
        $this->player()->update([
            'team_id' => null,
        ]);
        $this->game()->teams()->each(fn ($t) => $t->updateModelWithStateData());
        $this->game()->modifiers()->each(fn ($m) => $m->updateModelWithStateData());
    }
}
