<?php

namespace App\Events\MorningRoutine;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerExitedRoom extends Event
{
    use HasActivePlayer, HasChallenge, HasGame;

    public function validate()
    {
        $locations = $this->challenge()->challenge_data['player_locations'] ?? [];
        $current = $locations[$this->player_id] ?? 'hallway';

        $this->assert($current !== 'hallway', 'Player is already in the hallway.');
    }

    public function apply(ChallengeState $challenge)
    {
        $challenge->challenge_data['player_locations'][$this->player_id] = 'hallway';
    }

    public function handle(ChallengeState $state)
    {
        \App\Models\Challenge::find($this->challenge_id)?->update([
            'challenge_data' => $state->challenge_data,
        ]);
    }
}
