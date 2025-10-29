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
@endphp

<div x-data="{ selectedScoutSpace: null }">
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

                    $bgColor = $isPlayerSpace ? 'background-color: #fef3c7;' : ($isSelected ? 'background-color: #93c5fd;' : ($isAccessible ? 'background-color: #e5e7eb;' : ''));
                    $border = $isSelected ? 'border: 3px solid #3b82f6;' : 'border: 1px solid #ccc;';
                    $cursor = $isClickable ? 'cursor: pointer;' : '';

                    $spaceData = isset($grid[$y][$x]) ? $grid[$y][$x] : null;
                @endphp
                <div
                    style="{{ $border }} padding: 1px; min-height: 10px; display: flex; align-items: center; justify-content: center; {{ $bgColor }} cursor: pointer;"
                    wire:click="$set('round_properties.{{ \App\Modifiers\Classes\FarmMap::key() }}.{{ $element['property_name'] }}', '{{ $coordinate }}')"
                    @if($isScoutable && $spaceData)
                        x-on:click="selectedScoutSpace = {{ json_encode($spaceData) }}"
                    @endif
                >
                    @if(isset($grid[$y][$x]))
                        @if($isPlayerSpace)
                            <flux:icon.user variant="micro"/>
                        @elseif($isScoutable)
                            <flux:icon.eye variant="micro"/>
                        @endif
                    @else
                        <div style="color: #999;">Empty</div>
                    @endif
                </div>
            @endfor
        @endfor
    </div>

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
                                <div>Stage: <span x-text="selectedScoutSpace.field_status.stage"></span></div>
                                <div x-show="selectedScoutSpace.field_status.owner_team_id">
                                    Owner: <span x-text="Object.values(@js($this->teams)).find(team => String(team.id) === String(selectedScoutSpace.field_status.owner_team_id))?.name || 'Unknown'"></span>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="selectedScoutSpace.road_status?.level">
                        <tr class="border-b">
                            <td class="py-1 px-2 font-semibold bg-gray-100">Road</td>
                            <td class="py-1 px-2">
                                <div>Level: <span x-text="selectedScoutSpace.road_status.level"></span></div>
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
                            <td class="py-1 px-2" x-text="selectedScoutSpace.player_ids.length + ' player(s)'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </template>
    </div>

    <div class="flex flex-wrap gap-2 mt-4 justify-end">
        @php
            $selectedValue = $this->round_properties[\App\Modifiers\Classes\FarmMap::key()][$element['property_name']] ?? null;
            $isAccessibleSpace = !empty($selectedValue) && in_array($selectedValue, $element['accessible_spaces']);
        @endphp
        @if($player_space)
            <x-button
                wire:loading.attr="disabled"
                wire:key="button-{{ \App\Modifiers\Classes\FarmMap::key() }}-move"
                variant="primary"
                wire:click="callClassAction('move', 'modifier', '{{ \App\Modifiers\Classes\FarmMap::key() }}', null)"
                :disabled="!$isAccessibleSpace"
            >
                💪 Move
            </x-button>
        @else
            <x-button
                wire:loading.attr="disabled"
                wire:key="button-{{ \App\Modifiers\Classes\FarmMap::key() }}-spawn"
                variant="primary"
                wire:click="callClassAction('move', 'modifier', '{{ \App\Modifiers\Classes\FarmMap::key() }}', null)"
                :disabled="empty($selectedValue)"
            >
                🏠 Spawn
            </x-button>
        @endif
    </div>
</div>