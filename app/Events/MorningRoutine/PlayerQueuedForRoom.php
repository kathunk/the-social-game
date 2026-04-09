<?php

namespace App\Events\MorningRoutine;

use App\Challenges\MorningRoutine\Rewards\RewardRegistry;
use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Models\Challenge;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerQueuedForRoom extends Event
{
    use HasActivePlayer, HasChallenge, HasGame;

    public string $room;

    public function validate()
    {
        $data = $this->challenge()->challenge_data;
        $location = $data['player_locations'][$this->player_id] ?? 'hallway';

        $this->assert($location === 'hallway', 'You must be in the hallway to queue.');
        $this->assert(($data['room_queues'][$this->room] ?? null) === null, 'That room is already queued.');

        // Confirm the room is occupied (otherwise just enter directly)
        $occupant = collect($data['player_locations'] ?? [])
            ->first(fn ($loc) => $loc === $this->room);
        $this->assert($occupant !== null, 'That room is empty - just enter it.');
    }

    public function apply(ChallengeState $challenge)
    {
        $challenge->challenge_data['room_queues'][$this->room] = $this->player_id;

        // Dispatch onPlayerQueuedForRoom hook for all taken rewards
        foreach ($challenge->challenge_data['taken_rewards'] ?? [] as $r => $rewards_in_room) {
            foreach ($rewards_in_room as $reward_key => $taker_id) {
                $reward = RewardRegistry::find($reward_key);
                if ($reward && $reward->hasEffect()) {
                    $effect_class = $reward->effect_class;
                    $effect = new $effect_class;
                    $challenge->challenge_data = $effect->onPlayerQueuedForRoom(
                        taker_id: (int) $taker_id,
                        queueing_player_id: $this->player_id,
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
