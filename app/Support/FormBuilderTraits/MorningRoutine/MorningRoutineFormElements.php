<?php

namespace App\Support\FormBuilderTraits\MorningRoutine;

use App\Models\Player;
use Illuminate\Support\Collection;

trait MorningRoutineFormElements
{
    public function morningRoutine(
        Player $player,
        string $current_location,
        array $player_locations,
        array $rooms,
        Collection $players,
    ): static {

        $players_by_id = $players->keyBy('id');

        // Build a map of room => occupant name (or null)
        $room_states = [];
        foreach ($rooms as $room) {
            $occupant_id = collect($player_locations)
                ->filter(fn ($loc) => $loc === $room)
                ->keys()
                ->first();

            $room_states[$room] = [
                'occupied' => $occupant_id !== null,
                'is_current_player' => $occupant_id == $player->id,
                'occupant_name' => $occupant_id ? $players_by_id[$occupant_id]?->name : null,
            ];
        }

        // Players visible in the hallway
        $hallway_players = collect($player_locations)
            ->filter(fn ($loc) => $loc === 'hallway')
            ->keys()
            ->map(fn ($id) => [
                'id' => $id,
                'name' => $players_by_id[$id]?->name ?? 'Unknown',
                'is_current_player' => $id == $player->id,
            ])
            ->values()
            ->toArray();

        $this->elements[] = [
            'type' => 'morning_routine',
            'current_location' => $current_location,
            'room_states' => $room_states,
            'hallway_players' => $hallway_players,
            'player_name' => $player->name,
        ];

        return $this;
    }
}
