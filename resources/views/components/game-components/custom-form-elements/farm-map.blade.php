@props(['element'])

@php
    $maxX = max(array_column($element['spaces'], 'x-index'));
    $maxY = max(array_column($element['spaces'], 'y-index'));

    $grid = [];
    foreach ($element['spaces'] as $space) {
        $grid[$space['y-index']][$space['x-index']] = $space;
    }

    // Generate column headers (A, B, C, ...)
    $columnHeaders = [];
    for ($i = 0; $i <= $maxX; $i++) {
        $columnHeaders[] = chr(65 + $i); // A=65, B=66, etc.
    }

    $player_space = collect($element['spaces'])->filter(fn ($space) => in_array($this->player->id, $space['player_ids']))->first();

    // Parse accessible spaces (e.g., "A1", "B2") into coordinates
    $accessible_coordinates = collect($element['accessible_spaces'])->map(function($coord) {
        $column = ord(strtoupper($coord[0])) - 65; // Convert A->0, B->1, etc.
        $row = intval(substr($coord, 1)) - 1; // Convert 1->0, 2->1, etc.
        return ['x' => $column, 'y' => $row];
    });

    // Create a map of scoutable spaces for easy lookup
    $scoutableSpacesMap = collect($element['scoutable_spaces'])->keyBy(function($space) {
        return $space['x-index'] . ',' . $space['y-index'];
    })->toArray();

    // Create a map of player IDs to names for all players in the game
    // Use strings as keys to avoid JavaScript number truncation with snowflake IDs
    $allPlayerIds = collect($element['spaces'])->pluck('player_ids')->flatten()->unique();
    $playerNames = $allPlayerIds->mapWithKeys(function($playerId) {
        $player = \App\Models\Player::find($playerId);
        return [(string)$playerId => $player ? $player->name : 'Unknown'];
    })->toArray();

    // Get unique types from visited spaces for the legend
    $visitedTypes = collect($element['spaces'])
        ->filter(fn($space) => isset($space['visited_by_player_ids']) && in_array($this->player->id, $space['visited_by_player_ids']))
        ->pluck('type')
        ->unique()
        ->sort()
        ->values();

    // Map types to display names and colors
    $typeInfo = [
        'desert' => ['name' => 'Desert', 'color' => '#deb887'],
        'grass' => ['name' => 'Grass', 'color' => '#228b22'],
        'volcano' => ['name' => 'Volcano', 'color' => '#ff6347'],
        'mountain' => ['name' => 'Mountain', 'color' => '#a9a9a9'],
        'swamp' => ['name' => 'Swamp', 'color' => '#9acd32'],
        'ash_heap' => ['name' => 'Ash Heap', 'color' => '#654321'],
        'tunnel' => ['name' => 'Secret Tunnel', 'color' => '#000000'],
        'town' => ['name' => 'Town', 'color' => '#7851a9'],
        'fertile_ashland' => ['name' => 'Fertile Ashland', 'color' => '#8b7355'],
    ];

    // Check what exists on the map for legend
    $hasPlayerPosition = $player_space !== null;
    $hasFriendlyFields = collect($element['spaces'])->contains(fn($s) => ($s['field_status']['owner_team_id'] ?? null) === $this->player->team_id && ($s['field_status']['level'] ?? 0) > 0);
    $hasFriendlySilos = collect($element['spaces'])->contains(fn($s) => ($s['silo_status']['owner_team_id'] ?? null) === $this->player->team_id && ($s['silo_status']['level'] ?? 0) > 0);
    $hasTeammatesOnMap = collect($element['spaces'])->contains(function($s) {
        return collect($s['player_ids'] ?? [])->contains(function($pid) {
            $p = \App\Models\Player::find($pid);
            return $p && $p->id !== $this->player->id && $p->team_id === $this->player->team_id;
        });
    });
    $hasScoutables = !empty($element['scoutable_spaces']);
@endphp

<div x-data="{ selectedScoutSpace: null, playerNames: @js($playerNames) }">
    <div style="display: grid; grid-template-columns: 30px repeat({{ $maxX + 1 }}, 1fr); gap: 4px;">
        <!-- Empty cell for top-left corner -->
        <div style="border: 1px solid #ccc; padding: 8px; min-height: 10px; display: flex; align-items: center; justify-content: center; background-color: #f5f5f5;">
        </div>
        
        <!-- Column headers (A, B, C, ...) -->
        @foreach($columnHeaders as $header)
            <div style="border: 1px solid #ccc; padding: 2px; min-height: 10px; display: flex; align-items: center; justify-content: center; background-color: #f5f5f5;">
                {{ $header }}
            </div>
        @endforeach
        
        <!-- Grid rows with row headers -->
        @for($y = 0; $y <= $maxY; $y++)
            <!-- Row header (1, 2, 3, ...) -->
            <div style="border: 1px solid #ccc; padding: 1px; min-height: 10px; display: flex; align-items: center; justify-content: center; background-color: #f5f5f5;">
                {{ $y + 1 }}
            </div>
            
            <!-- Grid cells for this row -->
            @for($x = 0; $x <= $maxX; $x++)
                @php
                    $isPlayerSpace = $player_space && ($x === $player_space['x-index'] && $y === $player_space['y-index']);
                    $isAccessible = $accessible_coordinates->contains(fn($coord) => $coord['x'] === $x && $coord['y'] === $y);
                    $coordinate = chr(65 + $x) . ($y + 1); // Convert x,y to A1 format
                    $selectedValue = $this->round_properties[\App\Modifiers\Classes\FarmMap::key()][$element['property_name']] ?? null;
                    $isSelected = $selectedValue === $coordinate;
                    $isScoutable = collect($element['scoutable_spaces'])->contains(fn($space) => $space['x-index'] === $x && $space['y-index'] === $y);
                    $isClickable = $isAccessible || $isScoutable;

                    $spaceData = isset($grid[$y][$x]) ? $grid[$y][$x] : null;
                    // Convert player_ids to strings to avoid JavaScript number truncation with snowflake IDs
                    if ($spaceData && isset($spaceData['player_ids'])) {
                        $spaceData['player_ids'] = array_map('strval', $spaceData['player_ids']);
                    }

                    // Check if player has visited this space
                    $hasVisited = $spaceData && isset($spaceData['visited_by_player_ids']) && in_array($this->player->id, $spaceData['visited_by_player_ids']);

                    // Set type-based color if visited
                    $typeColor = '';
                    if ($hasVisited && $spaceData) {
                        $typeColor = match($spaceData['type']) {
                            'desert' => 'background-color: #deb887;', // tan/burlywood
                            'grass' => 'background-color: #228b22;', // forest green
                            'volcano' => 'background-color: #ff6347;', // lava orange
                            'mountain' => 'background-color: #a9a9a9;', // alpine stone gray
                            'swamp' => 'background-color: #9acd32;', // yellowish-green toxic
                            'ash_heap' => 'background-color: #654321;', // dark brown
                            'tunnel' => 'background-color: #000000; color: #fff;', // pitch black with white text
                            'town' => 'background-color: #7851a9;', // royal purple
                            'fertile_ashland' => 'background-color: #8b7355;', // lighter brown
                            default => '',
                        };
                    }

                    $bgColor = $typeColor;
                    $border = $isSelected ? 'border: 3px solid #3b82f6;' : 'border: 1px solid #ccc;';
                    $cursor = $isClickable ? 'cursor: pointer;' : '';

                    // Check for friendly structures
                    $hasFriendlyField = $spaceData && ($spaceData['field_status']['owner_team_id'] ?? null) === $this->player->team_id && ($spaceData['field_status']['level'] ?? 0) > 0;
                    $hasFriendlySilo = $spaceData && ($spaceData['silo_status']['owner_team_id'] ?? null) === $this->player->team_id && ($spaceData['silo_status']['level'] ?? 0) > 0;
                    $hasTeammates = $spaceData && collect($spaceData['player_ids'] ?? [])->filter(function($pid) {
                        $p = \App\Models\Player::find($pid);
                        return $p && $p->id !== $this->player->id && $p->team_id === $this->player->team_id;
                    })->isNotEmpty();
                @endphp
                <div
                    style="{{ $border }} padding: 1px; min-height: 10px; display: flex; align-items: center; justify-content: center; {{ $bgColor }} cursor: pointer;"
                    wire:click="$set('round_properties.{{ \App\Modifiers\Classes\FarmMap::key() }}.{{ $element['property_name'] }}', '{{ $coordinate }}')"
                    x-on:click="selectedScoutSpace = {{ $isScoutable && $spaceData ? json_encode($spaceData) : 'null' }}"
                >
                    @if(isset($grid[$y][$x]))
                        <div style="display: flex; flex-direction: column; gap: 1px; align-items: center; justify-content: center; font-size: 10px;">
                            @if($isPlayerSpace)
                                <flux:icon.user variant="micro" class="text-white"/>
                            @endif
                            @if($hasFriendlyField)
                                <span style="font-size: 10px;" title="Friendly Field">🌾</span>
                            @endif
                            @if($hasFriendlySilo)
                                <span style="font-size: 10px;" title="Friendly Silo">🏠</span>
                            @endif
                            @if($hasTeammates)
                                <flux:icon.user-group variant="micro" class="text-white"/>
                            @endif
                            @if($isScoutable)
                                <flux:icon.eye variant="micro"/>
                            @endif
                        </div>
                    @else
                        <div style="color: #999; font-size: 8px;">Empty</div>
                    @endif
                </div>
            @endfor
        @endfor
    </div>

    <!-- Move/Spawn Button -->
    <div class="flex flex-wrap gap-2 mt-4 justify-end">
        @php
            $selectedValue = $this->round_properties[\App\Modifiers\Classes\FarmMap::key()][$element['property_name']] ?? null;
            $isAccessibleSpace = !empty($selectedValue) && in_array($selectedValue, $element['accessible_spaces']);
            $cost = 1;
            $cost_suffix = '';
            $can_afford_to_move = false;

            if (!empty($selectedValue) && $player_space) {
                $x = ord(strtoupper($selectedValue[0])) - 65;
                $y = intval(substr($selectedValue, 1)) - 1;
                $cost = \App\Modifiers\Classes\FarmMap::costToMove($player_space, $grid[$y][$x]);
                $can_afford_to_move = $element['actions'] >= $cost;
                $cost_suffix = $cost > 0 ? '💪'.str_repeat('💪', $cost - 1) : '';
            }
        @endphp
        @if($player_space && $isAccessibleSpace && $can_afford_to_move)
            <x-button
                wire:loading.attr="disabled"
                wire:key="button-{{ \App\Modifiers\Classes\FarmMap::key() }}-move"
                variant="primary"
                wire:click="callClassAction('move', 'modifier', '{{ \App\Modifiers\Classes\FarmMap::key() }}', null)"
            >
                {{ $cost_suffix }} Move
            </x-button>
        @elseif(!$player_space && !empty($selectedValue))
            <x-button
                wire:loading.attr="disabled"
                wire:key="button-{{ \App\Modifiers\Classes\FarmMap::key() }}-spawn"
                variant="primary"
                wire:click="callClassAction('move', 'modifier', '{{ \App\Modifiers\Classes\FarmMap::key() }}', null)"
            >
                🏠 Spawn
            </x-button>
        @endif
    </div>

    <!-- Terrain Type Legend -->
    @if($visitedTypes->isNotEmpty())
        <div class="mt-4 p-3 border border-gray-300 rounded bg-gray-50">
            <h3 class="font-semibold mb-2 text-sm">Discovered Terrain Types:</h3>
            <div class="flex flex-wrap gap-3">
                @foreach($visitedTypes as $type)
                    @if(isset($typeInfo[$type]))
                        <div class="flex items-center gap-2">
                            <div style="width: 24px; height: 24px; background-color: {{ $typeInfo[$type]['color'] }}; border: 1px solid #ccc; border-radius: 3px; {{ $type === 'tunnel' ? 'color: #fff;' : '' }}"></div>
                            <span class="text-sm">{{ $typeInfo[$type]['name'] }}</span>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Map Icons Legend -->
            <h3 class="font-semibold mt-3 mb-2 text-sm">Map Icons:</h3>
            <div class="flex flex-wrap gap-3">
                @if($hasPlayerPosition)
                    <div class="flex items-center gap-2">
                        <flux:icon.user variant="micro" class="text-white" style="width: 16px; height: 16px; background-color: #333; padding: 2px; border-radius: 3px;"/>
                        <span class="text-sm">Your Position</span>
                    </div>
                @endif
                @if($hasFriendlyFields)
                    <div class="flex items-center gap-2">
                        <span style="font-size: 14px;">🌾</span>
                        <span class="text-sm">Friendly Field</span>
                    </div>
                @endif
                @if($hasFriendlySilos)
                    <div class="flex items-center gap-2">
                        <span style="font-size: 14px;">🏠</span>
                        <span class="text-sm">Friendly Silo</span>
                    </div>
                @endif
                @if($hasTeammatesOnMap)
                    <div class="flex items-center gap-2">
                        <flux:icon.user-group variant="micro" class="text-white" style="width: 16px; height: 16px; background-color: #333; padding: 2px; border-radius: 3px;"/>
                        <span class="text-sm">Teammates</span>
                    </div>
                @endif
                @if($hasScoutables)
                    <div class="flex items-center gap-2">
                        <flux:icon.eye variant="micro" style="width: 16px; height: 16px;"/>
                        <span class="text-sm">Scoutable Space</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Scoutable space information -->
    <div x-show="selectedScoutSpace !== null" class="mt-4 p-4 border border-gray-300 rounded bg-gray-50">
        <h3 class="font-bold mb-2" x-text="selectedScoutSpace ? (String.fromCharCode(65 + selectedScoutSpace['x-index']) + (selectedScoutSpace['y-index'] + 1) + ' Information') : 'Space Information'"></h3>
        <template x-if="selectedScoutSpace">
            <table class="w-full text-sm border-collapse">
                <tbody>
                    <tr class="border-b">
                        <td class="py-1 px-2 font-semibold bg-gray-100">Type</td>
                        <td class="py-1 px-2" x-text="selectedScoutSpace.type.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')"></td>
                    </tr>

                    <template x-if="selectedScoutSpace.field_status?.level">
                        <tr class="border-b">
                            <td class="py-1 px-2 font-semibold bg-gray-100">Field</td>
                            <td class="py-1 px-2">
                                <div>Level: <span x-text="selectedScoutSpace.field_status.level"></span></div>
                                <div>Stage: <span x-text="selectedScoutSpace.field_status.stage.startsWith('mature_') ? 'mature' : selectedScoutSpace.field_status.stage"></span></div>
                                <div x-show="selectedScoutSpace.field_status.owner_team_id">
                                    Owner: <span x-text="Object.values(@js($this->teams)).find(team => String(team.id) === String(selectedScoutSpace.field_status.owner_team_id))?.name || 'Unknown'"></span>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="selectedScoutSpace.road_exists">
                        <tr class="border-b">
                            <td class="py-1 px-2 font-semibold bg-gray-100">Road</td>
                            <td class="py-1 px-2">
                                <div>✅</div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="selectedScoutSpace.watchtower_exists">
                        <tr class="border-b">
                            <td class="py-1 px-2 font-semibold bg-gray-100">Watchtower</td>
                            <td class="py-1 px-2">
                                <div>✅</div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="selectedScoutSpace.walls_exist">
                        <tr class="border-b">
                            <td class="py-1 px-2 font-semibold bg-gray-100">Walls</td>
                            <td class="py-1 px-2">
                                <div>✅</div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="selectedScoutSpace.silo_status?.level">
                        <tr class="border-b">
                            <td class="py-1 px-2 font-semibold bg-gray-100">Silo</td>
                            <td class="py-1 px-2">
                                <div>Level: <span x-text="selectedScoutSpace.silo_status.level"></span></div>
                                <div x-show="selectedScoutSpace.silo_status.owner_team_id">
                                    Owner: <span x-text="Object.values(@js($this->teams)).find(team => String(team.id) === String(selectedScoutSpace.silo_status.owner_team_id))?.name || 'Unknown'"></span>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="selectedScoutSpace.player_ids && selectedScoutSpace.player_ids.length > 0">
                        <tr class="border-b">
                            <td class="py-1 px-2 font-semibold bg-gray-100">Players</td>
                            <td class="py-1 px-2" x-text="selectedScoutSpace.player_ids.map(id => playerNames[id] || 'Unknown').join(', ')"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </template>
    </div>

</div>