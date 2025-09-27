<?php

namespace App\Support\FormBuilderTraits;

use App\Models\Player;

trait FarmFormElements
{
    public function farmMap(array $spaces, Player $player)
    {
        $this->elements[] = [
            'type' => 'farm_map',
            'property_name' => 'farm_map',
            'spaces' => $spaces,
        ];

        return $this;
    }

    public function farmActions(
        array $player_actions, 
        array $player_space, 
        array $farm_map, 
        bool $can_move
    ) {
        $this->elements[] = [
            'type' => 'farm_actions',
            'property_name' => 'farm_actions',
            'actions' => $player_actions,
            'player_space' => $player_space,
            'farm_map' => $farm_map,
            'can_move' => $can_move,
        ];

        if ($can_move) {

            $player_x = $player_space['x-index'];
            $player_y = $player_space['y-index'];

            $available_spaces = collect($farm_map)->filter(function ($space) use ($player_x, $player_y) {

                $x = $space['x-index'];
                $y = $space['y-index'];

                return (
                    ($x === $player_x + 1 && $y === $player_y) ||
                    ($x === $player_x - 1 && $y === $player_y) ||
                    ($x === $player_x && $y === $player_y + 1) ||
                    ($x === $player_x && $y === $player_y - 1)
                );
            })->map(function ($space) {
                return chr(65 + $space['x-index']) . ($space['y-index'] + 1);
            })->toArray();
            $this->select(
                label: 'Move to a space (cost: 1 action)',
                options: $available_spaces,
                property_name: 'space_string',
                validation_rules: 'required|in:'.implode(',', $available_spaces),
                validation_messages: ['required' => 'Space is required', 'in' => 'Space is invalid'],
                placeholder: 'Select a space...',
            );
            $this->buttonGroup()
                ->button('Move', 'move', ['space_string'])
                ->endGroup();
        }

        return $this;
    }
}
