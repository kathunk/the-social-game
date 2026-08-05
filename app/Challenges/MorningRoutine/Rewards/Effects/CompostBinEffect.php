<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

class CompostBinEffect extends RewardEffect
{
    public function onChallengeEnded(int $taker_id, array $challenge_data): int
    {
        $penalty = $challenge_data['player_penalties'][$taker_id] ?? 0;

        // Cancel the penalty AND add it as a bonus: 2 * penalty
        return 2 * $penalty;
    }
}
