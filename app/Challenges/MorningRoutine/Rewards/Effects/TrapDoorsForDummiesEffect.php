<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

/**
 * Pure movement effect: lets the player move between unoccupied rooms
 * without going through the hallway. Checked by the challenge action methods
 * via the active_effects flag.
 */
class TrapDoorsForDummiesEffect extends RewardEffect
{
    public function onTaken(int $player_id, array $challenge_data): array
    {
        return $this->arm($challenge_data, $player_id, 'trap_doors_unlocked');
    }
}
