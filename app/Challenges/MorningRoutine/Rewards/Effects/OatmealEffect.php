<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

class OatmealEffect extends RewardEffect
{
    public function onTaken(int $player_id, array $challenge_data, ?array $rng = null): array
    {
        return $this->arm($challenge_data, $player_id, 'extra_reward_bathroom');
    }
}
