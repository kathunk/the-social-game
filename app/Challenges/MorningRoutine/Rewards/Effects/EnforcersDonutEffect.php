<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

class EnforcersDonutEffect extends RewardEffect
{
    public function onTaken(int $player_id, array $challenge_data): array
    {
        return $this->arm($challenge_data, $player_id, 'donut_double_bust');
    }

    public function onPlayerBusted(int $taker_id, int $busted_player_id, int $buster_player_id, int &$penalty_amount, array $challenge_data): array
    {
        // Only fires if the taker is the one doing the busting
        if ($taker_id !== $buster_player_id) {
            return $challenge_data;
        }

        if (! $this->isArmed($challenge_data, $taker_id, 'donut_double_bust')) {
            return $challenge_data;
        }

        $penalty_amount *= 2;

        return $this->disarm($challenge_data, $taker_id, 'donut_double_bust');
    }
}
