<?php

namespace App\Support\Traits;

use App\Models\Player;

trait MorningRoutineFormElements
{
    public function roomMove(array $rooms)
    {
        return $this->select(
            property_name: 'room',
            options: $rooms,
            label: 'Move to a room',
            placeholder: 'Select a room...',
            validation_rules: 'required|in:'.implode(',', $rooms),
            validation_messages: [
                'required' => 'Must select a room',
                'in' => 'Must select a valid room',
            ],
            searchable: false,
        )
            ->buttonGroup()
            ->button(
                label: 'Move',
                action: 'move',
                properties_to_validate: ['room'],
            )
            ->endGroup();
    }

    public function actionsForCurrentRoom(Player $player)
    {
        return $this->subtitle($player->name);
    }
}