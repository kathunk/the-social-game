<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

/**
 * Pure UI effect: when in the hallway, this player can see room mess levels.
 * Checked by MorningRoutineFormElements via the active_effects flag.
 */
class MonocleEffect extends RewardEffect
{
    public function onTaken(int $player_id, array $challenge_data): array
    {
        return $this->arm($challenge_data, $player_id, 'monocle_xray');
    }
}
