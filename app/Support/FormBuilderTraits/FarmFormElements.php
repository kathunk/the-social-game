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
        array $player_skills,
        array $farm_map, 
        bool $can_move,
        bool $can_plant_farm,
        int $current_player_id = null
    ) {
        // Generate sprite configuration for the player's current space
        $sprite_config = $this->buildSpaceSpriteConfig($player_space, $current_player_id);

        $this->elements[] = [
            'type' => 'farm_actions',
            'property_name' => 'farm_actions',
            'actions' => $player_actions,
            'limit' => $player_skills['Strategist'] * 2 + 3,
            'player_space' => $player_space,
            'player_skills' => $player_skills,
            'farm_map' => $farm_map,
            'can_move' => $can_move,
            'can_plant_farm' => $can_plant_farm,
            'sprite_config' => $sprite_config,
        ];

        if ($can_plant_farm) {
            $this->buttonGroup()
                ->button('Plant farm', 'plantFarm')
                ->endGroup();
        }

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
            })->mapWithKeys(function ($space) {
                $pretty = chr(65 + $space['x-index']) . ($space['y-index'] + 1);
                return [$pretty => $pretty];
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

    /**
     * Build sprite configuration for a space
     */
    protected function buildSpaceSpriteConfig(array $player_space, int $current_player_id = null): array
    {
        if (empty($player_space)) {
            return [
                'viewBox' => [0, 0, 1000, 1000],
                'background' => 'grass',
                'overlays' => [],
            ];
        }

        $config = [
            'viewBox' => [0, 0, 1000, 1000],
            'background' => $player_space['type'] ?? 'grass',
            'overlays' => [],
        ];

        // Add road overlay if present
        $road_status = $player_space['road_status'] ?? [];
        if (!empty($road_status['owner_team_id'])) {
            $config['overlays'][] = $this->buildRoadOverlay($road_status);
        }

        // Add silo overlay if present
        $silo_status = $player_space['silo_status'] ?? [];
        if (!empty($silo_status['owner_team_id'])) {
            $config['overlays'][] = $this->buildSiloOverlay($silo_status);
        }

        // Add farm overlay if present
        $farm_status = $player_space['farm_status'] ?? [];
        if (!empty($farm_status['level'])) {
            $config['overlays'][] = $this->buildFarmOverlay($farm_status);
        }

        // Add player overlays
        $player_ids = $player_space['player_ids'] ?? [];
        foreach ($player_ids as $index => $player_id) {
            $config['overlays'][] = $this->buildPlayerOverlay($player_id, $index, $current_player_id);
        }

        return $config;
    }

    /**
     * Build road overlay configuration
     */
    protected function buildRoadOverlay(array $road_status): array
    {
        // Roads are smaller and positioned at the edge
        return [
            'type' => 'road',
            'x' => 100, // Left side of space
            'y' => 500, // Middle vertically
            'scale' => 0.3, // Much smaller scale
            'rotate' => 0,
            'anchor' => 'center',
            'z' => 5,
        ];
    }

    /**
     * Build silo overlay configuration
     */
    protected function buildSiloOverlay(array $silo_status): array
    {
        return [
            'type' => 'silo',
            'x' => 200, // Left side of space
            'y' => 800, // Near bottom
            'scale' => 0.4, // Smaller scale
            'rotate' => 0,
            'anchor' => 'bottom',
            'z' => 15,
        ];
    }

    /**
     * Build farm overlay configuration
     */
    protected function buildFarmOverlay(array $farm_status): array
    {
        $level = $farm_status['level'] ?? 1;
        $scale = min(0.4, 0.2 + ($level * 0.05)); // Much smaller scale

        return [
            'type' => 'farm',
            'x' => 800, // Right side of space
            'y' => 800, // Near bottom
            'scale' => $scale,
            'rotate' => 0,
            'anchor' => 'bottom',
            'z' => 20,
        ];
    }

    /**
     * Build player overlay configuration
     */
    protected function buildPlayerOverlay(int $player_id, int $index, int $current_player_id = null): array
    {
        // Distribute players around the center of the space with smaller scale
        $positions = [
            [500, 500], // Center
            [400, 400], // Top-left
            [600, 400], // Top-right
            [400, 600], // Bottom-left
            [600, 600], // Bottom-right
        ];
        
        $position = $positions[$index % count($positions)];
        
        // Check if this is the current player
        $is_current_player = ($current_player_id !== null && $player_id === $current_player_id);
        
        // Generate a color based on player ID for consistency
        $colors = ['#ff6b6b', '#4ecdc4', '#45b7d1', '#96ceb4', '#feca57', '#ff9ff3', '#54a0ff'];
        $color = $colors[$player_id % count($colors)];

        return [
            'type' => 'player',
            'x' => $position[0],
            'y' => $position[1],
            'scale' => $is_current_player ? 0.4 : 0.3, // Slightly larger for current player
            'rotate' => 0,
            'anchor' => 'center',
            'z' => $is_current_player ? 60 : 50, // Higher z-index for current player
            'fill' => $color,
            'stroke' => $is_current_player ? '#000' : 'none', // Black border for current player
            'stroke-width' => $is_current_player ? '3' : '0',
            'is_current_player' => $is_current_player, // Add flag for potential use in template
        ];
    }
}
