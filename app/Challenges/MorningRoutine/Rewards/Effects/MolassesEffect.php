<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

class MolassesEffect extends RewardEffect
{
    public function onTaken(int $player_id, array $challenge_data, ?array $rng = null): array
    {
        return $this->arm($challenge_data, $player_id, 'molasses_revenge');
    }

    public function onPlayerBusted(int $taker_id, int $busted_player_id, int $buster_player_id, int &$penalty_amount, array $challenge_data): array
    {
        // Only fires when the taker is the busted player
        if ($taker_id !== $busted_player_id) {
            return $challenge_data;
        }

        if (! $this->isArmed($challenge_data, $taker_id, 'molasses_revenge')) {
            return $challenge_data;
        }

        // Add 2 mess-equivalent penalty to the buster
        $challenge_data['player_penalties'][$buster_player_id] =
            ($challenge_data['player_penalties'][$buster_player_id] ?? 0) + 2;

        return $this->disarm($challenge_data, $taker_id, 'molasses_revenge');
    }
}
