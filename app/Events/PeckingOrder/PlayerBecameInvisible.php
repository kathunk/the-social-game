<?php

namespace App\Events\PeckingOrder;

use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasPlayer;
use App\Models\Challenge;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerBecameInvisible extends Event
{
    use HasChallenge, HasGame, HasPlayer;

    public function apply(ChallengeState $challenge)
    {
        $challenge->challenge_data['invisible_player_ids'][] = $this->player_id;
    }

    public function handle()
    {
        Challenge::find($this->challenge_id)->updateModelWithStateData();
    }
}
