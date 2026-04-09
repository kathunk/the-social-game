<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

class LuckySocksEffect extends RewardEffect
{
    public function onPlayerExitedRoom(int $taker_id, int $exiting_player_id, string $room, array $challenge_data): array
    {
        // Only fires when the taker is the one exiting (entering hallway)
        if ($taker_id !== $exiting_player_id) {
            return $challenge_data;
        }

        $queues = $challenge_data['room_queues'] ?? [];

        // Was anyone queued for the room they just left?
        $queued_for_their_room = ($queues[$room] ?? null) !== null;

        if ($queued_for_their_room) {
            return $challenge_data;
        }

        // Are any other rooms queued?
        $other_room_queued = collect($queues)
            ->filter(fn ($pid, $r) => $r !== $room && $pid !== null)
            ->isNotEmpty();

        if (! $other_room_queued) {
            return $challenge_data;
        }

        $challenge_data['player_points'][$taker_id] =
            ($challenge_data['player_points'][$taker_id] ?? 0) + 3;

        return $challenge_data;
    }
}
