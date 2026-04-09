<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

use App\Challenges\MorningRoutine\Rewards\RewardRegistry;

class JunkDrawerEffect extends RewardEffect
{
    public function onTaken(int $player_id, array $challenge_data): array
    {
        $available = $challenge_data['available_rewards']['kitchen'] ?? [];
        $taken_keys = array_keys($challenge_data['taken_rewards']['kitchen'] ?? []);
        $excluded = array_merge($available, $taken_keys, ['junk_drawer']);

        $candidates = collect(RewardRegistry::forRoom('kitchen'))
            ->reject(fn ($r) => in_array($r->key, $excluded, true))
            ->values();

        if ($candidates->isEmpty()) {
            return $challenge_data;
        }

        $pull = $candidates->random();

        // Credit points and mess from the pulled reward
        $challenge_data['player_points'][$player_id] =
            ($challenge_data['player_points'][$player_id] ?? 0) + $pull->points;
        $challenge_data['room_mess']['kitchen'] =
            ($challenge_data['room_mess']['kitchen'] ?? 0) + $pull->mess;

        // Toast the player about what they pulled
        $challenge_data['toasts'][$player_id][] = [
            'type' => 'junk_drawer',
            'message' => "Junk drawer pull: {$pull->name} (+{$pull->points} pts, +{$pull->mess} mess)",
            'created_at' => now()->timestamp,
        ];

        return $challenge_data;
    }
}
