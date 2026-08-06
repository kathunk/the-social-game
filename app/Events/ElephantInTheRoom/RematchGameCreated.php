<?php

namespace App\Events\ElephantInTheRoom;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Models\Modifier;
use App\States\ModifierState;
use Thunk\Verbs\Event;

/**
 * Records the rematch game's id on the ORIGINAL game's rematch modifier so
 * every player's post-game card can forward them to the new game.
 */
class RematchGameCreated extends Event
{
    use HasGame, HasModifier;

    public int $rematch_game_id;

    public function validate()
    {
        $this->assert(
            empty($this->state(ModifierState::class)->modifier_data['rematch_game_id']),
            'The rematch has already been created.'
        );
    }

    public function apply(ModifierState $modifier)
    {
        $modifier->modifier_data['rematch_game_id'] = $this->rematch_game_id;
    }

    public function handle(ModifierState $state)
    {
        Modifier::find($this->modifier_id)?->update([
            'modifier_data' => $state->modifier_data,
        ]);
    }
}
