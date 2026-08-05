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
        $data = $this->state(ChallengeState::class)->challenge_data;
        $queue = $data['room_queues'][$this->room] ?? [];

        $this->assert(in_array($this->player_id, $queue, true), 'You are not queued for this room.');
    }

    public function apply(ChallengeState $challenge)
    {
        $challenge->challenge_data['room_queues'][$this->room] = array_values(array_filter(
            $challenge->challenge_data['room_queues'][$this->room] ?? [],
            fn ($id) => $id !== $this->player_id,
        ));
    }

    public function handle(ChallengeState $state)
    {
        Challenge::find($this->challenge_id)?->update([
            'challenge_data' => $state->challenge_data,
        ]);
    }
}
