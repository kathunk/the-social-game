<?php

namespace App\Events\ElephantInTheRoom;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasPlayer;
use App\Models\Modifier;
use App\States\GameState;
use App\States\ModifierState;
use Thunk\Verbs\Event;

class PlayerWantsRematch extends Event
{
    use HasGame, HasModifier, HasPlayer;

    public function validate()
    {
        $this->assert(
            $this->state(GameState::class)->status === 'ended',
            'The game is not over yet.'
        );

        $data = $this->state(ModifierState::class)->modifier_data;

        $this->assert(
            ! in_array($this->player_id, $data['rematch_votes'] ?? []),
            'You already asked for a rematch.'
        );

        $this->assert(
            empty($data['rematch_game_id']),
            'The rematch has already been created.'
        );
    }

    public function apply(ModifierState $modifier)
    {
        $modifier->modifier_data['rematch_votes'][] = $this->player_id;
    }

    public function handle(ModifierState $state)
    {
        Modifier::find($this->modifier_id)?->update([
            'modifier_data' => $state->modifier_data,
        ]);
    }
}
