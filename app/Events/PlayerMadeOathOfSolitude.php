<?php

namespace App\Events;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\States\ModifierState;
use App\States\PlayerState;
use Thunk\Verbs\Event;

class PlayerMadeOathOfSolitude extends Event
{
    use HasActivePlayer, HasGame, HasModifier;

    public function validate()
    {
        $data = $this->state(ModifierState::class)->modifier_data;

        $this->assert(
            ! in_array($this->player()->id, $data['lone_wolves']),
            'You have already made an oath of solitude',
        );

        $this->assert(
            ! array_key_exists($this->player()->id, $data['pairs']),
            'You are already in an alliance',
        );
    }

    public function apply(ModifierState $modifier)
    {
        $modifier->modifier_data['lone_wolves'][] = $this->player()->id;

        $modifier->modifier_data['offers'][$this->player()->id] = null;
    }

    public function applyToPlayer(PlayerState $player)
    {
        $player->addToScoreHistory(
            points: 3,
            description: 'Oath of Solitude',
            is_hidden: true,
        );
    }

    public function handle()
    {
        $this->modifier()->updateModelWithStateData();
        $this->player()->updateModelWithStateData();
    }
}
