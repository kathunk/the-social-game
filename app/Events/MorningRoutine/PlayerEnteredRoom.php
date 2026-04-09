<?php

namespace App\Events\MorningRoutine;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerEnteredRoom extends Event
{
    use HasActivePlayer, HasChallenge, HasGame;

    public string $room;

    public function validate()
    {
        $this->assert(
            in_array($this->room, ['bathroom', 'laundry', 'study', 'kitchen']),
            'Invalid room.'
        );

        $locations = $this->challenge()->challenge_data['player_locations'] ?? [];
        $current = $locations[$this->player_id] ?? 'hallway';

        $this->assert($current === 'hallway', 'Player must be in the hallway to enter a room.');

        $occupant = collect($locations)->first(fn ($loc) => $loc === $this->room);
        $this->assert($occupant === null, 'Room is already occupied.');
    }

    public function apply(ChallengeState $challenge)
    {
        $challenge->challenge_data['player_locations'][$this->player_id] = $this->room;
    }

    public function handle(ChallengeState $state)
    {
        \App\Models\Challenge::find($this->challenge_id)?->update([
            'challenge_data' => $state->challenge_data,
        ]);
    }
}
