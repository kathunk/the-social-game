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

        return $this->credit($challenge_data, $taker_id, 1, 'Janitor\'s uniform: cleaned up');
    }
}
