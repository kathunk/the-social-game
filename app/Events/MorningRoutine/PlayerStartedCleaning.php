<?php

namespace App\Events\MorningRoutine;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Models\Challenge;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerStartedCleaning extends Event
{
    use HasActivePlayer, HasChallenge, HasGame;

    public string $room;

    public int $started_at;

    public function validate()
    {
        $data = $this->challenge()->challenge_data;
        $location = $data['player_locations'][$this->player_id] ?? 'hallway';

        $this->assert($location === $this->room, 'You must be in the room to clean it.');
        $this->assert(($data['room_mess'][$this->room] ?? 0) > 0, 'Room is already clean.');
        $this->assert(! isset($data['cleaning_state'][$this->player_id]), 'You are already cleaning.');
    }

    public function apply(ChallengeState $challenge)
    {
        $challenge->challenge_data['cleaning_state'][$this->player_id] = [
            'room' => $this->room,
            'started_at' => $this->started_at,
        ];
    }

    public function handle(ChallengeState $state)
    {
        Challenge::find($this->challenge_id)?->update([
            'challenge_data' => $state->challenge_data,
        ]);
    }
}
