<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

class HolyRobesEffect extends RewardEffect
{
    public function onPlayerQueuedForRoom(int $taker_id, int $queueing_player_id, string $room, array $challenge_data): array
    {
        // Only fires when the queueing player is queueing for the room the taker is currently in
        $taker_location = $challenge_data['player_locations'][$taker_id] ?? 'hallway';

        if ($taker_location !== $room) {
            return $challenge_data;
        }

        if ($queueing_player_id === $taker_id) {
            return $challenge_data;
        }

        $room_mess = $challenge_data['room_mess'][$room] ?? 0;

        if ($room_mess > 0) {
            return $challenge_data;
        }

        $challenge_data['player_points'][$taker_id] =
            ($challenge_data['player_points'][$taker_id] ?? 0) + 3;

        return $challenge_data;
    }
}
