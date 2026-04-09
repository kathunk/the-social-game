<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

use App\Challenges\MorningRoutine\Rewards\RewardRegistry;

class IntermittentFastingEffect extends RewardEffect
{
    public function onChallengeEnded(int $taker_id, array $challenge_data): int
    {
        $taken_kitchen = collect($challenge_data['taken_rewards']['kitchen'] ?? [])
            ->filter(fn ($pid) => $pid === $taker_id)
            ->isNotEmpty();

        return $taken_kitchen ? 0 : 3;
    }
}
