<?php

namespace App\Events\MorningRoutine;

use App\Challenges\MorningRoutine\Rewards\RewardRegistry;
use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Models\Challenge;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerExitedRoom extends Event
{
    use HasActivePlayer, HasChallenge, HasGame;

    public function validate()
    {
        $locations = $this->challenge()->challenge_data['player_locations'] ?? [];
        $current = $locations[$this->player_id] ?? 'hallway';

        $this->assert($current !== 'hallway', 'Player is already in the hallway.');
    }

    public function apply(ChallengeState $challenge)
    {
        $room = $challenge->challenge_data['player_locations'][$this->player_id];
        $challenge->challenge_data['player_locations'][$this->player_id] = 'hallway';

        // Dispatch onPlayerExitedRoom hook for all taken rewards
        foreach ($challenge->challenge_data['taken_rewards'] ?? [] as $r => $rewards_in_room) {
            foreach ($rewards_in_room as $reward_key => $taker_id) {
                $reward = RewardRegistry::find($reward_key);
                if ($reward && $reward->hasEffect()) {
                    $effect_class = $reward->effect_class;
                    $effect = new $effect_class;
                    $challenge->challenge_data = $effect->onPlayerExitedRoom(
                        taker_id: (int) $taker_id,
                        exiting_player_id: $this->player_id,
                        room: $room,
                        challenge_data: $challenge->challenge_data,
                    );
                }
            }
        }
    }

    public function handle(ChallengeState $state)
    {
        Challenge::find($this->challenge_id)?->update([
            'challenge_data' => $state->challenge_data,
        ]);
    }
}
