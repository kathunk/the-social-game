<?php

namespace App\Events\MorningRoutine;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Models\Challenge;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerLeftHouse extends Event
{
    use HasActivePlayer, HasChallenge, HasGame;

    public function validate()
    {
        $data = $this->state(ChallengeState::class)->challenge_data;
        $location = $data['player_locations'][$this->player_id] ?? 'hallway';

        $this->assert($location !== 'left', 'You already left the house.');
        $this->assert($location === 'hallway', 'You must be in the hallway to leave the house.');
    }

    public function apply(ChallengeState $challenge)
    {
        $challenge->challenge_data['player_locations'][$this->player_id] = 'left';

        // Leaving the house abandons any line you were standing in
        foreach ($challenge->challenge_data['room_queues'] ?? [] as $room => $queue) {
            $challenge->challenge_data['room_queues'][$room] = array_values(array_filter(
                $queue ?? [],
                fn ($id) => $id !== $this->player_id,
            ));
        }

        $challenge->challenge_data['exit_order'][] = $this->player_id;

        // First out the door gets N points (N = player count), next N-1, and so on
        $player_count = count($challenge->challenge_data['player_locations'] ?? []);
        $position = count($challenge->challenge_data['exit_order']);
        $bonus = max($player_count - $position + 1, 1);

        $challenge->challenge_data['player_points'][$this->player_id] =
            ($challenge->challenge_data['player_points'][$this->player_id] ?? 0) + $bonus;

        $challenge->challenge_data['point_log'][] = [
            'player_id' => $this->player_id,
            'points' => $bonus,
            'type' => 'exit',
            'label' => "Out the door #{$position}",
        ];

        $challenge->challenge_data['toasts'][$this->player_id][] = [
            'type' => 'left_house',
            'message' => "You're out the door! #{$position} to leave: +{$bonus} points",
            'created_at' => now()->timestamp,
        ];
    }

    public function handle(ChallengeState $state)
    {
        Challenge::find($this->challenge_id)?->update([
            'challenge_data' => $state->challenge_data,
        ]);
    }
}
