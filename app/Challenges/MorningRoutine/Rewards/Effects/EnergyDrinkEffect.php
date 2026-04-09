<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

class EnergyDrinkEffect extends RewardEffect
{
    public function onTaken(int $player_id, array $challenge_data): array
    {
        $challenge_data['room_mess']['kitchen'] = 0;

        return $challenge_data;
    }
}
