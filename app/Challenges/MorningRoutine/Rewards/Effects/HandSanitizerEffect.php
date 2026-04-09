<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

class HandSanitizerEffect extends RewardEffect
{
    public function onTaken(int $player_id, array $challenge_data): array
    {
        $current_mess = $challenge_data['room_mess']['bathroom'] ?? 0;
        $challenge_data['room_mess']['bathroom'] = max(0, $current_mess - 5);

        return $challenge_data;
    }
}
