<?php

namespace App\Events\MorningRoutine;

use App\Challenges\MorningRoutine\MorningRoutineRound;
use App\Challenges\MorningRoutine\Rewards\RewardRegistry;
use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Models\Challenge;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerCleanedRoom extends Event
{
    use HasActivePlayer, HasChallenge, HasGame;

    public string $room;

    public int $finished_at;

    public function validate()
    {
        $data = $this->challenge()->challenge_data;
        $cleaning = $data['cleaning_state'][$this->player_id] ?? null;

        $this->assert($cleaning !== null, 'Player is not cleaning.');
        $this->assert($cleaning['room'] === $this->room, 'Player is cleaning a different room.');
    }

    public function apply(ChallengeState $challenge)
    {
        $cleaning = $challenge->challenge_data['cleaning_state'][$this->player_id];
        $elapsed = max(0, $this->finished_at - $cleaning['started_at']);
        $mess_to_remove = (int) floor($elapsed / MorningRoutineRound::SECONDS_PER_MESS_CLEAN);

        $current_mess = $challenge->challenge_data['room_mess'][$this->room] ?? 0;
        $mess_removed = min($mess_to_remove, $current_mess);

        $challenge->challenge_data['room_mess'][$this->room] = $current_mess - $mess_removed;
        unset($challenge->challenge_data['cleaning_state'][$this->player_id]);

        // Dispatch onRoomCleaned hook for all players' active rewards
        foreach ($challenge->challenge_data['taken_rewards'] ?? [] as $r => $rewards_in_room) {
            foreach ($rewards_in_room as $reward_key => $taker_id) {
                $reward = RewardRegistry::find($reward_key);
                if ($reward && $reward->hasEffect()) {
                    $effect_class = $reward->effect_class;
                    $effect = new $effect_class;
                    $challenge->challenge_data = $effect->onRoomCleaned(
                        taker_id: (int) $taker_id,
                        cleaning_player_id: $this->player_id,
                        room: $this->room,
                        mess_removed: $mess_removed,
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
