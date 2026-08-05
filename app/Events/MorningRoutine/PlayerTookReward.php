<?php

namespace App\Events\MorningRoutine;

use App\Challenges\MorningRoutine\Rewards\RewardRegistry;
use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Models\Challenge;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerTookReward extends Event
{
    use HasActivePlayer, HasChallenge, HasGame;

    public string $room;

    public string $reward_key;

    public function validate()
    {
        $reward = RewardRegistry::find($this->reward_key);
        $this->assert($reward !== null, 'Unknown reward.');
        $this->assert($reward->room === $this->room, 'Reward does not belong to this room.');

        $data = $this->state(ChallengeState::class)->challenge_data;

        $location = $data['player_locations'][$this->player_id] ?? 'hallway';
        $this->assert($location === $this->room, 'You must be in the room to take a reward.');

        $available = $data['available_rewards'][$this->room] ?? [];
        $this->assert(in_array($this->reward_key, $available, true), 'Reward is not available.');

        $taken = $data['taken_rewards'][$this->room] ?? [];
        $this->assert(! isset($taken[$this->reward_key]), 'Reward has already been taken.');

        $taken_in_rooms = $data['player_taken_in_rooms'][$this->player_id] ?? [];
        $already_took = in_array($this->room, $taken_in_rooms, true);
        $has_extra = ($data['active_effects'][$this->player_id]["extra_reward_{$this->room}"] ?? false) === true;

        $this->assert(
            ! $already_took || $has_extra,
            'You already took a reward in this room.'
        );
    }

    public function apply(ChallengeState $challenge)
    {
        $reward = RewardRegistry::find($this->reward_key);

        // Mark reward as taken (and remove from available)
        $challenge->challenge_data['taken_rewards'][$this->room][$this->reward_key] = $this->player_id;
        $challenge->challenge_data['available_rewards'][$this->room] = array_values(array_filter(
            $challenge->challenge_data['available_rewards'][$this->room] ?? [],
            fn ($k) => $k !== $this->reward_key,
        ));

        // Track room as taken-in (only once - duplicates are not added on extra rewards)
        $taken_in_rooms = $challenge->challenge_data['player_taken_in_rooms'][$this->player_id] ?? [];
        $already_took = in_array($this->room, $taken_in_rooms, true);

        if ($already_took) {
            // Consume the one-shot extra-reward flag
            unset($challenge->challenge_data['active_effects'][$this->player_id]["extra_reward_{$this->room}"]);
        } else {
            $challenge->challenge_data['player_taken_in_rooms'][$this->player_id][] = $this->room;
        }

        // Award points
        $challenge->challenge_data['player_points'][$this->player_id] =
            ($challenge->challenge_data['player_points'][$this->player_id] ?? 0) + $reward->points;

        $challenge->challenge_data['point_log'][] = [
            'player_id' => $this->player_id,
            'points' => $reward->points,
            'type' => 'reward',
            'label' => $reward->name,
        ];

        // Add mess
        $challenge->challenge_data['room_mess'][$this->room] =
            ($challenge->challenge_data['room_mess'][$this->room] ?? 0) + $reward->mess;

        // Apply effect's onTaken hook (may further mutate challenge_data)
        if ($reward->hasEffect()) {
            $effect_class = $reward->effect_class;
            $effect = new $effect_class;
            $challenge->challenge_data = $effect->onTaken($this->player_id, $challenge->challenge_data);
        }
    }

    public function handle(ChallengeState $state)
    {
        Challenge::find($this->challenge_id)?->update([
            'challenge_data' => $state->challenge_data,
        ]);
    }
}
