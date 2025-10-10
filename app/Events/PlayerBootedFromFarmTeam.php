<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\TeamState;
use App\States\PlayerState;
use App\States\ModifierState;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasTeam;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasModifier;

class PlayerBootedFromFarmTeam extends Event
{
    use HasGame, HasPlayer, HasTeam, HasModifier;

    public int $grain_in_possession;

    public function validate()
    {
        //
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

        $team->addToScoreHistory('🥾', $amount_to_transfer, $player->name . ' was booted from team and took ' . $amount_to_transfer . ' grain with them.');
    }

    public function handle()
    {
        $this->player()->update([
            'team_id' => null
        ]);
        $this->game()->teams()->each(fn($t) => $t->updateModelWithStateData());
        $this->game()->modifiers()->each(fn($m) => $m->updateModelWithStateData());
    }
}
