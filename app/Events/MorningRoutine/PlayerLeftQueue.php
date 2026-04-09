<?php

namespace App\Events\MorningRoutine;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Models\Challenge;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerLeftQueue extends Event
{
    use HasActivePlayer, HasChallenge, HasGame;

    public string $room;

    public function validate()
    {
        $data = $this->challenge()->challenge_data;
        $queued = $data['room_queues'][$this->room] ?? null;

        $this->assert($queued === $this->player_id, 'You are not queued for this room.');
    }

    public function apply(ChallengeState $challenge)
    {
        $challenge->challenge_data['room_queues'][$this->room] = null;
    }

    public function handle(ChallengeState $state)
    {
        Challenge::find($this->challenge_id)?->update([
            'challenge_data' => $state->challenge_data,
        ]);
    }
}
