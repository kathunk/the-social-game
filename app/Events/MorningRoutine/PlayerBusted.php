<?php

namespace App\Events\MorningRoutine;

use App\Challenges\MorningRoutine\Rewards\RewardRegistry;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Models\Challenge;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerBusted extends Event
{
    use HasChallenge, HasGame;

    public int $busted_player_id;

    public int $buster_player_id;

    public string $room;

    public int $mess_amount;

    public function apply(ChallengeState $challenge)
    {
        $penalty = $this->mess_amount;

        // Dispatch onPlayerBusted hook for all taken rewards
        // (effects may modify $penalty by reference, or add penalties to other players)
        foreach ($challenge->challenge_data['taken_rewards'] ?? [] as $r => $rewards_in_room) {
            foreach ($rewards_in_room as $reward_key => $taker_id) {
                $reward = RewardRegistry::find($reward_key);
                if ($reward && $reward->hasEffect()) {
                    $effect_class = $reward->effect_class;
                    $effect = new $effect_class;
                    $challenge->challenge_data = $effect->onPlayerBusted(
                        taker_id: (int) $taker_id,
                        busted_player_id: $this->busted_player_id,
                        buster_player_id: $this->buster_player_id,
                        penalty_amount: $penalty,
                        challenge_data: $challenge->challenge_data,
                    );
                }
            }
        }

        // Apply final penalty
        $challenge->challenge_data['player_penalties'][$this->busted_player_id] =
            ($challenge->challenge_data['player_penalties'][$this->busted_player_id] ?? 0) + $penalty;

        if ($penalty !== 0) {
            $challenge->challenge_data['point_log'][] = [
                'player_id' => $this->busted_player_id,
                'points' => -$penalty,
                'type' => 'bust',
                'label' => "Busted leaving the {$this->room} ({$this->mess_amount} mess)",
            ];
        }

        // Add toast notifications
        $now = now()->timestamp;
        $challenge->challenge_data['toasts'][$this->busted_player_id][] = [
            'type' => 'busted',
            'message' => "You got busted leaving the {$this->room} with {$this->mess_amount} mess! -{$penalty} points",
            'created_at' => $now,
        ];
        $challenge->challenge_data['toasts'][$this->buster_player_id][] = [
            'type' => 'busted_someone',
            'message' => "You busted someone leaving the {$this->room} with {$this->mess_amount} mess!",
            'created_at' => $now,
        ];
    }

    public function handle(ChallengeState $state)
    {
        Challenge::find($this->challenge_id)?->update([
            'challenge_data' => $state->challenge_data,
        ]);
    }
}
