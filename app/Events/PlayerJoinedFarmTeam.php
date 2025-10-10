<?php

namespace App\Events;

use App\Models\Team;
use App\Models\Player;
use Thunk\Verbs\Event;
use App\States\GameState;
use App\States\TeamState;
use App\States\PlayerState;
use App\States\ModifierState;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasTeam;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasModifier;
use App\Modifiers\Classes\FarmMap;

class PlayerJoinedFarmTeam extends Event
{
    use HasGame, HasPlayer, HasTeam, HasModifier;

    public ?int $requester_previous_team_id = null;
    public ?bool $player_is_last_on_team = false;
    public ?int $requester_grain = 0;

    public function validate()
    {
        //
    }

    public function applyToPlayer(PlayerState $player)
    {
        $player->team_id = $this->team_id;
    }

    public function applyToModifier(ModifierState $modifier)
    {
        $modifier->modifier_data['leaders'] = collect($modifier->modifier_data['leaders'])
            ->map(function ($player_id, $team_id) {
                if ($team_id === $this->requester_previous_team_id) {
                    $previous_team = TeamState::load($this->requester_previous_team_id);
                    $next_player_on_previous_team_id = $previous_team
                        ->player_ids->reject(fn ($p_id) => $p_id === $this->player_id)->first();


                    return $this->player_is_last_on_team 
                        ? null 
                        : $next_player_on_previous_team_id;
                }

                return $player_id;
            })->toArray();
    }

    public function applyToGame(GameState $game)
    {
        if ($this->requester_previous_team_id === null || ! $this->player_is_last_on_team) {
            return;
        }

        $map_state = $game->modifiers()->firstWhere('class_key', FarmMap::key());

        $map_state->modifier_data = collect($map_state->modifier_data)
            ->map(function ($space) {
                if ($space['silo_status']['owner_team_id'] === $this->requester_previous_team_id) {
                    $space['silo_status']['owner_team_id'] = $this->team_id;
                }

                if ($space['field_status']['owner_team_id'] === $this->requester_previous_team_id) {
                    $space['field_status']['owner_team_id'] = $this->team_id;
                }

                if ($space['road_status']['owner_team_id'] === $this->requester_previous_team_id) {
                    $space['road_status']['owner_team_id'] = $this->team_id;
                }

                return $space;
            })->toArray();
    }

    public function applyToTeam(TeamState $team)
    {
        $team->player_ids->push($this->player_id);
        $requester = PlayerState::load($this->player_id);

        $amount_to_transfer = $this->requester_grain;

        if ($this->requester_previous_team_id !== null) {
            $previous_team = TeamState::load($this->requester_previous_team_id);
            $previous_team->player_ids = $previous_team->player_ids->reject(fn ($player_id) => $player_id === $this->player_id);
        }

        if ($this->player_is_last_on_team) {
            $amount_to_transfer += $previous_team->score() - $this->requester_grain;
        }
        
        if ($this->requester_previous_team_id !== null) {
            $previous_team->addToScoreHistory('🫥', -$amount_to_transfer, $requester->name . ' left team and took all their grain with them.');
        }

        $team->addToScoreHistory('👋', $amount_to_transfer, $requester->name . ' joined team and brought ' . $amount_to_transfer . ' grain with them.');
    }

    public function handle()
    {
        $this->player()->update([
            'team_id' => $this->team_id
        ]);
        $this->game()->teams()->each(fn($t) => $t->updateModelWithStateData());
        $this->game()->modifiers()->each(fn($m) => $m->updateModelWithStateData());

        if ($this->player_is_last_on_team) {
            $previous_team = Team::find($this->requester_previous_team_id);
            $previous_team->updateModelWithStateData();
            $previous_team->delete();
        }
    }
}
