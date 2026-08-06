<?php

namespace App\Events\ElephantInTheRoom;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Models\Modifier;
use App\States\ModifierState;
use Thunk\Verbs\Event;

/**
 * Links an already-created rematch game to the ORIGINAL game's rematch
 * modifier. This event does NOT create the game — that's the generic
 * GameCreated/GameStarted flow via Game::fromTemplate — it only records the
 * pointer on ModifierState so
 * every player's post-game card can forward them to the new game.
 */
class RematchGameLinked extends Event
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
