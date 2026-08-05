<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

class LibrarianSweaterEffect extends RewardEffect
{
    public function onChallengeEnded(int $taker_id, array $challenge_data): int
    {
        $study_mess = $challenge_data['room_mess']['study'] ?? 0;

        return $study_mess === 0 ? 3 : 0;
    }
}
