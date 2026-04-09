<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

use App\Challenges\MorningRoutine\Rewards\RewardRegistry;

class MirrorEffect extends RewardEffect
{
    public function onChallengeEnded(int $taker_id, array $challenge_data): int
    {
        // Find all rewards taken by this player
        $taken_keys = collect($challenge_data['taken_rewards'] ?? [])
            ->flatMap(fn ($room_rewards) => collect($room_rewards)
                ->filter(fn ($pid) => $pid === $taker_id)
                ->keys()
                ->all())
            ->all();

        if (empty($taken_keys)) {
            return 0;
        }

        $lowest = collect($taken_keys)
            ->map(fn ($key) => RewardRegistry::find($key))
            ->filter()
            ->reject(fn ($r) => $r->key === 'mirror') // don't double the mirror itself
            ->sortBy('points')
            ->first();

        return $lowest?->points ?? 0;
    }
}
