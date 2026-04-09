<?php

namespace App\Events\MorningRoutine;

use App\Challenges\MorningRoutine\Rewards\RewardRegistry;
use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Models\Challenge;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerEnteredRoom extends Event
{
    use HasActivePlayer, HasChallenge, HasGame;

    public string $room;

    public bool $from_queue = false;

    public function validate()
    {
        $this->assert(
            in_array($this->room, ['bathroom', 'laundry', 'study', 'kitchen']),
            'Invalid room.'
        );

        // Read from state (not model) so events fired earlier in the same batch
        // are visible to validation
        $data = $this->state(ChallengeState::class)->challenge_data;
        $locations = $data['player_locations'] ?? [];
        $current = $locations[$this->player_id] ?? 'hallway';

        $this->assert($current === 'hallway', 'Player must be in the hallway to enter a room.');

        $occupant = collect($locations)->first(fn ($loc) => $loc === $this->room);
        $this->assert($occupant === null, 'Room is already occupied.');
    }

    public function apply(ChallengeState $challenge)
    {
        $challenge->challenge_data['player_locations'][$this->player_id] = $this->room;

        // If from queue, clear the queue
        if ($this->from_queue) {
            $challenge->challenge_data['room_queues'][$this->room] = null;
        }

        // Dispatch onPlayerEnteredRoom hook for all taken rewards
        foreach ($challenge->challenge_data['taken_rewards'] ?? [] as $r => $rewards_in_room) {
            foreach ($rewards_in_room as $reward_key => $taker_id) {
                $reward = RewardRegistry::find($reward_key);
                if ($reward && $reward->hasEffect()) {
                    $effect_class = $reward->effect_class;
                    $effect = new $effect_class;
                    $challenge->challenge_data = $effect->onPlayerEnteredRoom(
                        taker_id: (int) $taker_id,
                        entering_player_id: $this->player_id,
                        room: $this->room,
                        challenge_data: $challenge->challenge_data,
                    );
                }
            }
        }
    }

    public function handle(ChallengeState $state)
    {
        Challenge::find($this->challenge_id)?->update([
            'challenge_data' => $state->challenge_data,
        ]);
    }
}
