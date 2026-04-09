<?php

namespace App\Events\Laracon2025;

use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasPlayer;
use App\Models\Challenge;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerTripledOpponentsVote extends Event
{
    use HasChallenge, HasGame, HasPlayer;

    public int $tripled_player_id;

    public function apply(ChallengeState $challenge)
    {
        $challenge->challenge_data['tripled_player_ids'][$this->player_id] = $this->tripled_player_id;
    }

    public function handle()
    {
        Challenge::find($this->challenge_id)->updateModelWithStateData();
    }
}
