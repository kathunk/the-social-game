<?php

namespace App\Events\PeckingOrder;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerPlayedGrandstandGambit extends Event
{
    use HasActivePlayer, HasChallenge, HasGame;

    public int $points;

    public function apply(ChallengeState $challenge)
    {
        $challenge->challenge_data['choices'][$this->player_id] = $this->points;
    }

    public function handle()
    {
        $this->challenge()->updateModelWithStateData();
    }
}
