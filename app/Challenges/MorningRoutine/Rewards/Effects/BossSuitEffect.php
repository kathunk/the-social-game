<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

class BossSuitEffect extends RewardEffect
{
    public function onTaken(int $player_id, array $challenge_data): array
    {
        return $this->arm($challenge_data, $player_id, 'boss_suit_immunity');
    }

    public function onPlayerBusted(int $taker_id, int $busted_player_id, int $buster_player_id, int &$penalty_amount, array $challenge_data): array
    {
        if ($taker_id !== $busted_player_id) {
            return $challenge_data;
        }

        if (! $this->isArmed($challenge_data, $taker_id, 'boss_suit_immunity')) {
            return $challenge_data;
        }

        $penalty_amount = 0;

        return $this->disarm($challenge_data, $taker_id, 'boss_suit_immunity');
    }
}
