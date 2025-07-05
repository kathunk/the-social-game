<?php

namespace App\Events;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerGerrymanderedOpponent extends Event
{
    use HasActivePlayer, HasChallenge, HasGame;

    public int $gerrymandered_player_id;

    public function applyToChallenge(ChallengeState $challenge)
    {
        $challenge->challenge_data['gerrymandered_player_ids'][$this->player_id] = $this->gerrymandered_player_id;
    }

    public function handle()
    {
        $this->challenge()->updateModelWithStateData();
    }
}
