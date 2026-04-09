<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

class GamblersFallacyEffect extends RewardEffect
{
    public function onChallengeEnded(int $taker_id, array $challenge_data): int
    {
        return random_int(0, 1) === 1 ? 3 : -1;
    }
}
