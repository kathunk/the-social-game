<?php

namespace App\Events;

use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasPlayer;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerReadiedUp extends Event
{
    use HasChallenge, HasGame, HasPlayer;

    public function applyToChallenge(ChallengeState $challenge)
    {
        $challenge->challenge_data['has_readied_up'][] = $this->player_id;
    }

    public function handle()
    {
        $this->challenge()->updateModelWithStateData();
    }
}
