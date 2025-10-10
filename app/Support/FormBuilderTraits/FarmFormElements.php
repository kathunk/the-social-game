<?php

namespace App\Support\FormBuilderTraits;

use App\Models\Team;
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
        bool $can_plant_field,
        bool $can_harvest_field,
        bool $can_build_silo,
        bool $silo_exists,
        bool $can_upgrade_silo,
        bool $can_withdraw_silo,
        bool $can_deposit_silo,
        Player $player,
        array $all_leader_ids
    ) {
        // Generate sprite configuration for the player's current space
        $sprite_config = $this->buildSpaceSpriteConfig($player_space, $player);

        // @farmtodo is this array even necessary?
        $this->elements[] = [
            'type' => 'farm_actions',
            'property_name' => 'farm_actions',
            'actions' => $player_actions,
            'limit' => $player_actions['action_limit'],
            'player_space' => $player_space,
            'player_skills' => $player_skills,
            'farm_map' => $farm_map,
            'can_move' => $can_move,
            'can_plant_field' => $can_plant_field,
            'can_harvest_field' => $can_harvest_field,
            'can_build_silo' => $can_build_silo,
            'sprite_config' => $sprite_config,
            'leader_ids' => $all_leader_ids,
        ];

        if ($player_space['field_status']['level'] > 0) {
            $this->divider();
            $this->title('Field (level: ' . $player_space['field_status']['level'] . ')')
                ->subtitle('Owned by ' . Team::find($player_space['field_status']['owner_team_id'])->name)
                ->subtitle('Stage: ' . $player_space['field_status']['stage'])
                ->when($player_space['field_status']['stage'] === 'mature', function ($builder) use ($player_space, $can_harvest_field) {
                    $builder->subtitle('Quantity: ' . $player_space['field_status']['quantity'])
                    ->when($can_harvest_field, function ($builder) {
                        $builder->buttonGroup()
                            ->button('Harvest 💪', 'harvestField')
                            ->endGroup();
                    });
                });
        }

        if ($can_plant_field) {
            $this->buttonGroup()
                ->button('Plant field 💪', 'plantField')
                ->endGroup();
        }

        if ($can_build_silo) {
            $this->buttonGroup()
                ->button('Build silo 💪', 'buildSilo')
                ->endGroup();
        }

        if ($silo_exists) {
            $amount_in_silo = $player_space['silo_status']['amount'];
            $silo_capacity = $player_space['silo_status']['capacity'];
            $player_grain = $player_actions['grain'];
            $player_capacity = $player_actions['grain_capacity'];

            $withdrawable = min($amount_in_silo, $player_capacity - $player_grain);
            $depositable = min($player_grain, $silo_capacity - $amount_in_silo);

            $this->divider()->title('Silo (level: ' . $player_space['silo_status']['level'] . ')')
                ->subtitle('Owned by ' . Team::find($player_space['silo_status']['owner_team_id'])->name)
                ->subtitle('Amount: ' . $amount_in_silo . ' / ' . $silo_capacity)
                ->when($can_withdraw_silo, function ($builder) use ($withdrawable) {
                    $this->select(
                        label: 'Withdraw amount',
                        property_name: 'withdraw_amount',
                        validation_rules: 'required|in:'.implode(',', collect(range(1, $withdrawable))->toArray()),
                        validation_messages: ['required' => 'Amount is required', 'numeric' => 'Amount must be a number', 'min' => 'Amount must be greater than 0'],
                        options: collect(range(1, $withdrawable))->mapWithKeys(function ($amount) {
                            return [$amount => $amount];
                        })->toArray(),
                        placeholder: 'Select an amount...',
                    )
                    ->buttonGroup()
                        ->button('Withdraw', 'withdrawSilo')
                        ->endGroup();
                })
                ->when($can_deposit_silo, function ($builder) use ($depositable) {
                    $this->select(
                        label: 'Deposit amount',
                        property_name: 'deposit_amount',
                        validation_rules: 'required|in:'.implode(',', collect(range(1, $depositable))->toArray()),
                        validation_messages: ['required' => 'Amount is required', 'numeric' => 'Amount must be a number', 'min' => 'Amount must be greater than 0'],
                        options: collect(range(1, $depositable))->mapWithKeys(function ($amount) {
                            return [$amount => $amount];
                        })->toArray(),
                        placeholder: 'Select an amount...',
                    )
                    ->buttonGroup()
                        ->button('Deposit', 'depositSilo')
                        ->endGroup();
                })
                ->when($can_upgrade_silo, function ($builder) {
                    $this->buttonGroup()
                        ->button('Upgrade 💪', 'upgradeSilo')
                        ->endGroup();
                });
                
        }

        if ($can_move) {
            $this->divider()->title('Move to another space');
            // @farmtodo player can wrap around map like a globe lol
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
                label: 'Select a space',
                options: $available_spaces,
                property_name: 'space_string',
                validation_rules: 'required|in:'.implode(',', $available_spaces),
                validation_messages: ['required' => 'Space is required', 'in' => 'Space is invalid'],
                placeholder: 'Select a space...',
            );
            $this->buttonGroup()
                ->button('Move 💪', 'move', ['space_string'])
                ->endGroup();
        }

        return $this;
    }

    /**
     * Build sprite configuration for a space
     */
    protected function buildSpaceSpriteConfig(array $player_space, Player $player): array
    {
        if (empty($player_space)) {
            return [
                'viewBox' => [0, 0, 1600, 1000],
                'background' => 'grass',
                'overlays' => [],
            ];
        }

        $config = [
            'viewBox' => [0, 0, 1600, 1000],
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

        // Add field overlay if present
        $field_status = $player_space['field_status'] ?? [];

        if (!empty($field_status['level'])) {
            $overlay = $this->buildFarmOverlay($field_status);
            $config['overlays'][] = $overlay;

            // Add level number as text overlay
            $config['overlays'][] = [
                'type' => 'text',
                'text' => (string)$field_status['level'],
                'x' => 1200 + (65 - 50) * 0.3, // Adjust for badge position (65,73 in symbol coords)
                'y' => 700 + (73 - 50) * 0.3,
                'font-size' => 8,
                'fill' => '#000',
                'text-anchor' => 'middle',
                'dominant-baseline' => 'middle',
                'z' => 21, // Above the field
            ];
        }

        // Add player overlays
        $player_ids = $player_space['player_ids'] ?? [];
        foreach ($player_ids as $index => $player_id) {
            $config['overlays'][] = $this->buildPlayerOverlay($player_id, $index, $player);
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
            'x' => 200, // Left side of space
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
            'x' => 400, // Left-center of space
            'y' => 800, // Near bottom
            'scale' => 0.4, // Smaller scale
            'rotate' => 0,
            'anchor' => 'bottom',
            'z' => 15,
        ];
    }

    /**
     * Build farm/field overlay configuration
     *
     * Layout grid (1600x1000 viewBox):
     * - Road: left edge (x=200)
     * - Silo: left-center (x=400, y=800)
     * - Players: center area (x=700-900, y=400-600)
     * - Field: right side, middle-bottom (x=1200, y=700)
     */
    protected function buildFarmOverlay(array $farm_status): array
    {
        $level = $farm_status['level'] ?? 1;
        $stage = strtolower($farm_status['stage'] ?? 'seedlings'); // seedlings, sprouts, mature, rotted

        return [
            'type' => 'field-' . $stage,
            'x' => 1200, // Right side of space
            'y' => 700, // Middle-bottom area
            'scale' => 0.3, // Slightly smaller than silo
            'rotate' => 0,
            'anchor' => 'center', // Use center anchor like players (works correctly)
            'z' => 20,
        ];
    }

    /**
     * Build player overlay configuration
     */
    protected function buildPlayerOverlay(int $player_id, int $index, Player $player): array
    {
        // Distribute players around the center of the space with smaller scale
        $positions = [
            [800, 500], // Center
            [700, 400], // Top-left
            [900, 400], // Top-right
            [700, 600], // Bottom-left
            [900, 600], // Bottom-right
        ];
        
        $position = $positions[$index % count($positions)];
        
        // Check if this is the current player
        $is_current_player = ($player_id === $player->id);
        
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
