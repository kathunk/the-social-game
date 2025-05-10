<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\PlayerState;
use App\States\ModifierState;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasActiveGame;
use App\Events\Traits\HasActivePlayer;

class PlayerAssignedSecretAllyInTeamGame extends Event
{
    use HasActivePlayer, HasActiveGame, HasModifier;

    public int $ally_id;

    public function validate()
    {
        $paired_player_ids = collect($this->state(ModifierState::class)->modifier_data['pairs'])
            ->reduce(function ($carry, $pair) {
                $carry[] = $pair['player_1_id'];
                $carry[] = $pair['player_2_id'];

                return $carry;
            }, []);

        $this->assert(
            !$paired_player_ids->contains($this->player_id),
            'Player already has an ally'
        );

        $this->assert(
            !$paired_player_ids->contains($this->ally_id),
            'Ally already has an ally'
        );

        $this->assert(
            PlayerState::load($this->ally_id)->game_id === $this->game_id,
            'Ally is not in the game'
        );

        $this->assert(
            PlayerState::load($this->ally_id)->status === 'active',
            'Ally is not active'
        );
    }

    public function apply(ModifierState $modifier)
    {
        $ally = PlayerState::load($this->ally_id);

        $modifier->modifier_data['pairs'][] = [
            'player_1_id' => $this->player_id,
            'player_2_id' => $this->ally_id,
            'player_1_original_team_id' => $this->state(PlayerState::class)->team_id,
            'player_2_original_team_id' => $ally->team_id,
            'has_connected' => false,
        ];
    }

    public function handle()
    {
        $this->modifier()->updateModelWithStateData();
    }
}
