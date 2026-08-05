<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

class GamblersFallacyEffect extends RewardEffect
{
    public function prepareRng(int $player_id, array $challenge_data): ?array
    {
        return ['roll' => random_int(0, 1) === 1 ? 3 : -1];
    }

    public function onTaken(int $player_id, array $challenge_data, ?array $rng = null): array
    {
        // Stash the pre-rolled outcome; revealed (and scored) at the end of the game
        $challenge_data['active_effects'][$player_id]['gamblers_fallacy_roll'] = $rng['roll'] ?? 0;

        return $challenge_data;
    }

    public function onChallengeEnded(int $taker_id, array $challenge_data): int
    {
        return $challenge_data['active_effects'][$taker_id]['gamblers_fallacy_roll'] ?? 0;
    }
}
