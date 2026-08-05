<?php

namespace App\Support\FormBuilderTraits\MorningRoutine;

use App\Challenges\MorningRoutine\Rewards\RewardRegistry;
use App\Models\Player;
use Illuminate\Support\Collection;

trait MorningRoutineFormElements
{
    public function morningRoutine(
        Player $player,
        array $challenge_data,
        array $rooms,
        Collection $players,
        int $seconds_per_mess_clean,
        ?int $ends_at_ts = null,
    ): static {
        $players_by_id = $players->keyBy('id');

        $current_location = $challenge_data['player_locations'][$player->id] ?? 'hallway';
        $room_mess = $challenge_data['room_mess'] ?? [];
        $room_queues = $challenge_data['room_queues'] ?? [];
        $taken_rewards = $challenge_data['taken_rewards'] ?? [];
        $available_rewards = $challenge_data['available_rewards'] ?? [];
        $player_taken_in_rooms = $challenge_data['player_taken_in_rooms'] ?? [];
        $cleaning_state = $challenge_data['cleaning_state'] ?? [];
        $player_points = $challenge_data['player_points'] ?? [];
        $player_penalties = $challenge_data['player_penalties'] ?? [];
        $active_effects = $challenge_data['active_effects'][$player->id] ?? [];
        $toasts = $challenge_data['toasts'][$player->id] ?? [];

        // Build room states for hallway view
        $has_monocle = ! empty($active_effects['monocle_xray']);
        $room_states = [];
        foreach ($rooms as $room) {
            $occupant_id = collect($challenge_data['player_locations'] ?? [])
                ->filter(fn ($loc) => $loc === $room)
                ->keys()
                ->first();
            $queue = $room_queues[$room] ?? [];
            $queue_position = array_search($player->id, $queue, true);

            $room_states[$room] = [
                'occupied' => $occupant_id !== null,
                'occupant_is_current_player' => $occupant_id == $player->id,
                'queued' => count($queue) > 0,
                'queued_is_current_player' => $queue_position !== false,
                'queue_position' => $queue_position === false ? null : $queue_position + 1,
                'queue_names' => collect($queue)
                    ->map(fn ($id) => $players_by_id[$id]?->name ?? 'Unknown')
                    ->values()
                    ->all(),
                'mess_visible' => $has_monocle && $occupant_id !== null,
                'mess' => $room_mess[$room] ?? 0,
                'header' => RewardRegistry::ROOM_HEADERS[$room] ?? ucfirst($room),
            ];
        }

        // Players visible in the hallway
        $hallway_players = collect($challenge_data['player_locations'] ?? [])
            ->filter(fn ($loc) => $loc === 'hallway')
            ->keys()
            ->map(fn ($id) => [
                'id' => $id,
                'name' => $players_by_id[$id]?->name ?? 'Unknown',
                'is_current_player' => $id == $player->id,
            ])
            ->values()
            ->toArray();

        // Who's made it out the door, in exit order
        $exit_order = $challenge_data['exit_order'] ?? [];
        $exit_position = array_search($player->id, $exit_order, true);
        $exit_order_names = collect($exit_order)
            ->map(fn ($id) => $players_by_id[$id]?->name ?? 'Unknown')
            ->values()
            ->all();
        $still_inside_names = collect($challenge_data['player_locations'] ?? [])
            ->filter(fn ($loc) => $loc !== 'left')
            ->keys()
            ->map(fn ($id) => $players_by_id[$id]?->name ?? 'Unknown')
            ->values()
            ->all();
        $exit_bonus = collect($challenge_data['point_log'] ?? [])
            ->first(fn ($entry) => $entry['type'] === 'exit' && $entry['player_id'] == $player->id)['points'] ?? null;

        // Available rewards in the current room (no flavor text shown until taken)
        $room_rewards = [];
        if ($current_location !== 'hallway') {
            $available_keys = $available_rewards[$current_location] ?? [];
            foreach ($available_keys as $key) {
                $reward = RewardRegistry::find($key);
                if ($reward !== null) {
                    $room_rewards[] = [
                        'key' => $reward->key,
                        'name' => $reward->name,
                        'points' => $reward->points,
                        'mess' => $reward->mess,
                        'effect_description' => $reward->effect_description,
                        'has_effect' => $reward->hasEffect(),
                    ];
                }
            }
        }

        // Filter toasts to only the recent ones (last 15 seconds) so they don't
        // pile up forever in challenge_data
        $now_ts = now()->timestamp;
        $recent_toasts = collect($toasts)
            ->filter(fn ($t) => ($now_ts - ($t['created_at'] ?? 0)) <= 15)
            ->values()
            ->all();

        $already_took_in_current_room = $current_location !== 'hallway'
            && in_array($current_location, $player_taken_in_rooms[$player->id] ?? [], true);

        // Check for an "extra reward" flag (e.g. from coffee/oatmeal) that lets the
        // player take one more reward in the current room
        $has_extra_reward = $current_location !== 'hallway'
            && (($active_effects["extra_reward_{$current_location}"] ?? false) === true);

        $can_take_reward_here = ! $already_took_in_current_room || $has_extra_reward;

        // Cleaning state for current player
        $current_cleaning = $cleaning_state[$player->id] ?? null;

        $this->elements[] = [
            'type' => 'morning_routine',
            'current_location' => $current_location,
            'current_room_mess' => $current_location !== 'hallway' ? ($room_mess[$current_location] ?? 0) : 0,
            'current_room_header' => $current_location !== 'hallway'
                ? (RewardRegistry::ROOM_HEADERS[$current_location] ?? ucfirst($current_location))
                : null,
            'room_states' => $room_states,
            'hallway_players' => $hallway_players,
            'room_rewards' => $room_rewards,
            'already_took_in_current_room' => $already_took_in_current_room,
            'can_take_reward_here' => $can_take_reward_here,
            'has_extra_reward_flag' => $has_extra_reward,
            'cleaning_state' => $current_cleaning,
            'seconds_per_mess_clean' => $seconds_per_mess_clean,
            'player_points' => $player_points[$player->id] ?? 0,
            'player_penalties' => $player_penalties[$player->id] ?? 0,
            'toasts' => $recent_toasts,
            'player_name' => $player->name,
            'ends_at_ts' => $ends_at_ts,
            'has_left' => $current_location === 'left',
            'exit_position' => $exit_position === false ? null : $exit_position + 1,
            'exit_bonus' => $exit_bonus,
            'exit_order_names' => $exit_order_names,
            'still_inside_names' => $still_inside_names,
        ];

        return $this;
    }
}
