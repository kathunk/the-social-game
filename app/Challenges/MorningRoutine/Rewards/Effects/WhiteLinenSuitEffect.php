<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

class WhiteLinenSuitEffect extends RewardEffect
{
    public function onChallengeEnded(int $taker_id, array $challenge_data): int
    {
        $penalty = $challenge_data['player_penalties'][$taker_id] ?? 0;

        return $penalty === 0 ? 3 : 0;
    }
}
