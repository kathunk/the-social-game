<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

class AnarchistsCookbookEffect extends RewardEffect
{
    public function onChallengeEnded(int $taker_id, array $challenge_data): int
    {
        return $challenge_data['room_mess']['kitchen'] ?? 0;
    }
}
