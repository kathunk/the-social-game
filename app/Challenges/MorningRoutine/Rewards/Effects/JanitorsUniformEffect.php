<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

class JanitorsUniformEffect extends RewardEffect
{
    public function onRoomCleaned(int $taker_id, int $cleaning_player_id, string $room, int $mess_removed, array $challenge_data): array
    {
        if ($taker_id !== $cleaning_player_id) {
            return $challenge_data;
        }

        if ($mess_removed <= 0) {
            return $challenge_data;
        }

        $challenge_data['player_points'][$taker_id] =
            ($challenge_data['player_points'][$taker_id] ?? 0) + 1;

        return $challenge_data;
    }
}
