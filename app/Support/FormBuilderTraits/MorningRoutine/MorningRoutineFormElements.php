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
            $queued_id = $room_queues[$room] ?? null;

            $room_states[$room] = [
                'occupied' => $occupant_id !== null,
                'occupant_is_current_player' => $occupant_id == $player->id,
                'queued' => $queued_id !== null,
                'queued_is_current_player' => $queued_id == $player->id,
                'queued_player_name' => $queued_id ? ($players_by_id[$queued_id]?->name ?? null) : null,
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
                        'has_effect' => $reward->hasEffect(),
                    ];
                }
            }
        }

        $already_took_in_current_room = $current_location !== 'hallway'
            && in_array($current_location, $player_taken_in_rooms[$player->id] ?? [], true);

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
            'cleaning_state' => $current_cleaning,
            'seconds_per_mess_clean' => $seconds_per_mess_clean,
            'player_points' => $player_points[$player->id] ?? 0,
            'player_penalties' => $player_penalties[$player->id] ?? 0,
            'toasts' => $toasts,
            'player_name' => $player->name,
        ];

        return $this;
    }
}
