<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

use App\Challenges\MorningRoutine\Rewards\RewardRegistry;

class JunkDrawerEffect extends RewardEffect
{
    public function prepareRng(int $player_id, array $challenge_data): ?array
    {
        $available = $challenge_data['available_rewards']['kitchen'] ?? [];
        $taken_keys = array_keys($challenge_data['taken_rewards']['kitchen'] ?? []);
        $excluded = array_merge($available, $taken_keys, ['junk_drawer']);

        $candidates = collect(RewardRegistry::forRoom('kitchen'))
            ->reject(fn ($r) => in_array($r->key, $excluded, true))
            ->values();

        if ($candidates->isEmpty()) {
            return null;
        }

        return ['pull_key' => $candidates->random()->key];
    }

    public function onTaken(int $player_id, array $challenge_data, ?array $rng = null): array
    {
        $pull = isset($rng['pull_key']) ? RewardRegistry::find($rng['pull_key']) : null;

        if ($pull === null) {
            return $challenge_data;
        }

        // Credit points and mess from the pulled reward
        $challenge_data = $this->credit($challenge_data, $player_id, $pull->points, "Junk drawer pull: {$pull->name}");
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
