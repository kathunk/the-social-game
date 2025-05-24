<?php

namespace App\Events;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerStoleTheBacon extends Event
{
    use HasActivePlayer, HasChallenge, HasGame;

    public function apply(ChallengeState $challenge)
    {
        $challenge->challenge_data['choices'][$this->player_id] = 'steal';
    }

    public function handle()
    {
        $this->challenge()->updateModelWithStateData();
    }
}
