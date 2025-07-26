<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\GameState;
use App\States\PlayerState;
use App\States\ModifierState;
use App\Events\Traits\HasGame;
use App\States\ChallengeState;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasChallenge;

class PlayerLeftHouseInMorningRoutine extends Event
{
    use HasGame, HasPlayer, HasChallenge, HasModifier;

    public function validate(GameState $game, PlayerState $player, ChallengeState $challenge, ModifierState $modifier)
    {
        $this->assert(
            $modifier->modifier_data[$this->player_id]['Kitchen'] !== null,
            'Player has not completed the kitchen',
        );

        $this->assert(
            $modifier->modifier_data[$this->player_id]['Bathroom'] !== null,
            'Player has not completed the bathroom',
        );

        $this->assert(
            $modifier->modifier_data[$this->player_id]['Laundry'] !== null,
            'Player has not completed the laundry',
        );

        $this->assert(
            $modifier->modifier_data[$this->player_id]['Study'] !== null,
            'Player has not completed the study',
        );

        $this->assert(
            $challenge->challenge_data['rooms'][$this->player_id] === null,
            'Player is not in the hallway',
        );
    }

    public function applyToChallenge(ChallengeState $challenge)
    {
        //
    }

    public function applyToModifier(ModifierState $modifier)
    {
        //
    }

    public function handle()
    {
        $this->challenge()->updateModelWithStateData();
    }
}
