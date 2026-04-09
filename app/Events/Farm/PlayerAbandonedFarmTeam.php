<?php

namespace App\Events\Farm;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasTeam;
use App\States\PlayerState;
use App\States\TeamState;
use Thunk\Verbs\Event;

class PlayerAbandonedFarmTeam extends Event
{
    use HasGame, HasModifier, HasPlayer, HasTeam;

    public int $grain_in_possession;

    public function validate()
    {
        // Player was on team
        $player = $this->state(PlayerState::class);
        $this->assert(
            $player->team_id === $this->team_id,
            'Player is not on the specified team',
        );
    }

    public function applyToPlayer(PlayerState $player)
    {
        $player->team_id = null;
    }

    public function applyToTeam(TeamState $team)
    {
        $team->player_ids = $team->player_ids->reject(fn ($player_id) => $player_id === $this->player_id);
        $player = PlayerState::load($this->player_id);

        $amount_to_transfer = $this->grain_in_possession;

        $team->addToScoreHistory('🫥', -$amount_to_transfer, $player->name.' abandoned team and took '.$amount_to_transfer.' grain with them.');
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
