<?php

namespace App\Support\FormBuilderTraits;

use App\Models\Player;
use App\Models\Team;

trait FarmFormElements
{
    public function farmMap(
        array $spaces,
        Player $player,
        array $accessible_spaces,
        array $scoutable_spaces,
        ?array $player_space = null,
    ) {
        $this->elements[] = [
            'type' => 'farm_map',
            'property_name' => 'farm_map',
            'spaces' => $spaces,
            'player_space' => $player_space,
            'accessible_spaces' => $accessible_spaces,
            'property_name' => 'selected_space',
            'validation_rules' => 'required|in:'.implode(',', $accessible_spaces),
            'validation_messages' => ['required' => 'Space is required', 'in' => 'Space is invalid'],
            'scoutable_spaces' => $scoutable_spaces,
        ];

        return $this;
    }

    public function farmActions(
        array $player_actions,
        array $player_space,
        array $player_skills,
        array $farm_map,
        bool $can_plant_field,
        bool $can_harvest_field,
        bool $can_build_silo,
        bool $silo_exists,
        bool $can_upgrade_silo,
        bool $can_withdraw_silo,
        bool $can_deposit_silo,
        bool $can_build_road,
        Player $player,
        array $all_leader_ids,
        array $silo_seize_data,
        bool $can_see_history,
    ) {
        // Generate sprite configuration for the player's current space
        $sprite_config = $this->buildSpaceSpriteConfig($player_space, $player);

        $this->elements[] = [
            'type' => 'farm_actions',
            'property_name' => 'farm_actions',
            'actions' => $player_actions,
            'limit' => $player_actions['action_limit'],
            'player_space' => $player_space,
            'player_skills' => $player_skills,
            'farm_map' => $farm_map,
            'can_plant_field' => $can_plant_field,
            'can_harvest_field' => $can_harvest_field,
            'can_build_silo' => $can_build_silo,
            'can_build_road' => $can_build_road,
            'sprite_config' => $sprite_config,
            'leader_ids' => $all_leader_ids,
            'silo_seize_data' => $silo_seize_data,
            'can_see_history' => $can_see_history,
        ];

        if ($player_space['field_status']['level'] > 0) {
            $is_mature = $player_space['field_status']['stage'] === 'mature_1' || $player_space['field_status']['stage'] === 'mature_2' || $player_space['field_status']['stage'] === 'mature_3';
            $stage_display = str_starts_with($player_space['field_status']['stage'], 'mature_')
                ? 'mature'
                : $player_space['field_status']['stage'];

            $this->divider();
            $this->title('Field')
                ->subtitle('📈 Level: '.$player_space['field_status']['level'])
                ->subtitle('📜 Owner: '.Team::find($player_space['field_status']['owner_team_id'])->name)
                ->subtitle('🌱 Stage: '.$stage_display)
                ->when($is_mature, function ($builder) use ($player_space, $can_harvest_field, $player_skills) {
                    $harvest_cost = match ($player_skills['Farmer']) {
                        0 => '💪💪💪',
                        1 => '💪💪',
                        2 => '💪',
                        3 => '',
                        default => '',
                    };
                    $builder->subtitle('🌾 Grain: '.$player_space['field_status']['quantity'])
                        ->when($can_harvest_field, function ($builder) use ($harvest_cost) {
                            $builder->buttonGroup()
                                ->button('Harvest '.$harvest_cost, 'harvestField')
                                ->endGroup();
                        });
                });
        }

        if ($silo_exists) {
            $amount_in_silo = $player_space['silo_status']['amount'];
            $silo_capacity = $player_space['silo_status']['capacity'];
            $player_grain = $player_actions['grain'];
            $player_capacity = $player_actions['grain_capacity'];

            $withdrawable = min($amount_in_silo, $player_capacity - $player_grain);
            $depositable = min($player_grain, $silo_capacity - $amount_in_silo);

            $can_seize = $silo_seize_data['can_seize'];

            $defense_names = $silo_seize_data['defender_names'] ? ' from '.$silo_seize_data['defender_names'] : ' from players';
            $defense_text = '🛡️ '.$silo_seize_data['defense_power'].' defense: '.($player_space['silo_status']['level'] - 1).' from level + '.$silo_seize_data['defense_power_from_defenders'].$defense_names;

            $this->divider()
                ->title('Silo')
                ->subtitle('📈 Level: '.$player_space['silo_status']['level'])
                ->subtitle('📜 Owner: '.Team::find($player_space['silo_status']['owner_team_id'])->name)
                ->subtitle('🌾 Amount: '.$amount_in_silo.' / '.$silo_capacity)
                ->subtitle($defense_text)
                ->when($player_space['silo_status']['owner_team_id'] !== $player->team_id, function ($builder) use ($silo_seize_data) {
                    $builder->subtitle('⚔️ '.$silo_seize_data['attack_power_from_attackers'].' attack from: '.$silo_seize_data['attacker_names']);
                })
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
                })
                ->when($can_seize, function ($builder) {
                    $this->buttonGroup()
                        ->button('Seize 💪', 'seizeSilo')
                        ->endGroup();
                });
        }

        if ($can_plant_field || $can_build_silo || $can_build_road) {
            $plant_cost = match ($player_skills['Farmer']) {
                1 => '💪💪💪',
                2 => '💪💪',
                3 => '💪',
                default => '',
            };

            $build_cost = match ($player_skills['Builder']) {
                1 => '💪💪💪',
                2 => '💪💪',
                3 => '💪',
                default => '',
            };

            $this->buttonGroup()
                ->when($can_plant_field, function ($builder) use ($plant_cost) {
                    $builder->button('Plant field '.$plant_cost, 'plantField');
                })
                ->when($can_build_silo, function ($builder) use ($build_cost) {
                    $builder->button('Build silo '.$build_cost, 'buildSilo');
                })
                ->when($can_build_road, function ($builder) use ($build_cost) {
                    $builder->button('Build road '.$build_cost, 'buildRoad');
                })
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
        if (! empty($road_status['level']) || ! empty($road_status['owner_team_id'])) {
            $config['overlays'][] = $this->buildRoadOverlay($road_status);
        }

        // Add silo overlay if present
        $silo_status = $player_space['silo_status'] ?? [];
        if (! empty($silo_status['owner_team_id'])) {
            $config['overlays'][] = $this->buildSiloOverlay($silo_status);
        }

        // Add field overlay if present
        $field_status = $player_space['field_status'] ?? [];

        if (! empty($field_status['level'])) {
            $overlay = $this->buildFarmOverlay($field_status);
            $config['overlays'][] = $overlay;

            // Add level number as text overlay
            $config['overlays'][] = [
                'type' => 'text',
                'text' => (string) $field_status['level'],
                'x' => 1200 + (65 - 50) * 0.3, // Adjust for badge position (65,73 in symbol coords)
                'y' => 700 + (73 - 50) * 0.3,
                'font-size' => 8,
                'fill' => '#000',
                'text-anchor' => 'middle',
                'dominant-baseline' => 'middle',
                'z' => 21, // Above the field
            ];
        }

        $players = collect($player_space['player_ids'])->map(fn ($player_id) => Player::find($player_id));
        $players->each(function ($space_player, $index) use (&$config, $player) {
            $config['overlays'][] = $this->buildPlayerOverlay($space_player->id, $space_player->team_id, $index, $player);
        });

        return $config;
    }

    /**
     * Build road overlay configuration
     */
    protected function buildRoadOverlay(array $road_status): array
    {
        // Road spans horizontally across the entire space
        return [
            'type' => 'road',
            'x' => 0, // Start from left edge
            'y' => 450, // Middle of the space vertically
            'scaleX' => 16, // Stretch full width (1600 / 100)
            'scaleY' => 5, // Very tall so it's impossible to miss
            'rotate' => 0,
            'anchor' => 'top-left',
            'z' => 500, // Way above everything to ensure visibility
        ];
    }

    /**
     * Build silo overlay configuration
     */
    protected function buildSiloOverlay(array $silo_status): array
    {
        return [
            'type' => 'silo',
            'x' => 80, // Very far left
            'y' => 600, // Much higher, well above the road
            'scale' => 0.5, // Slightly larger
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
            'type' => 'field-'.$stage,
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
    protected function buildPlayerOverlay(int $player_id, int $team_id, int $index, Player $player): array
    {
        // Check if this is the current player
        $is_current_player = ($player_id === $player->id);

        // Check if this player is on the same team as the current player
        $is_same_team = ($team_id === $player->team_id);

        // Generate a consistent color based on team ID
        // All players on the same team share the same color
        $color = $this->generateTeamColor($team_id, $is_same_team);

        // Calculate position in a circular/radial pattern around center
        // This prevents overlapping and works for any number of players
        $position = $this->calculatePlayerPosition($index, $is_current_player);

        return [
            'type' => 'player',
            'x' => $position[0],
            'y' => $position[1],
            'scale' => $is_current_player ? 0.5 : 0.25, // Current player significantly larger
            'rotate' => 0,
            'anchor' => 'center',
            'z' => $is_current_player ? 100 : (50 + $index), // Current player always on top, others by index
            'fill' => $color,
            'stroke' => $is_current_player ? '#000' : '#333', // Black border for current, dark gray for others
            'stroke-width' => $is_current_player ? '4' : '2',
            'is_current_player' => $is_current_player,
        ];
    }

    /**
     * Generate a consistent color for a team based on their team ID
     */
    protected function generateTeamColor(int $team_id, bool $is_same_team): string
    {
        // Use team ID as seed for consistent colors
        // All players on the same team will get the same color
        $hue = ($team_id * 137.508) % 360; // Golden angle for good distribution

        // Current player's team: vibrant color
        // Other teams: more muted colors for distinction
        $saturation = $is_same_team ? 70 : 50;
        $lightness = $is_same_team ? 50 : 55;

        return "hsl({$hue}, {$saturation}%, {$lightness}%)";
    }

    /**
     * Calculate player position to avoid overlapping
     */
    protected function calculatePlayerPosition(int $index, bool $is_current_player): array
    {
        // Current player always in the center
        if ($is_current_player) {
            return [800, 500];
        }

        // Other players arranged in a circle around the center
        $centerX = 800;
        $centerY = 500;
        $radius = 150; // Distance from center

        // Calculate angle for this player (distribute evenly in a circle)
        // Start from top and go clockwise
        $angle = ($index * 2 * M_PI / max(1, $index + 1)) - (M_PI / 2);

        $x = $centerX + ($radius * cos($angle));
        $y = $centerY + ($radius * sin($angle));

        return [round($x), round($y)];
    }
}
