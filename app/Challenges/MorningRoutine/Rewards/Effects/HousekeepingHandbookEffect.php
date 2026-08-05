<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

class HousekeepingHandbookEffect extends RewardEffect
{
    public function onChallengeEnded(int $taker_id, array $challenge_data): int
    {
        $bathroom_mess = $challenge_data['room_mess']['bathroom'] ?? 0;

        return $bathroom_mess === 0 ? 4 : 0;
    }
}
