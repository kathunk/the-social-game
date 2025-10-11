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
@endphp

<div>
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
                    $isPlayerSpace = ($x === $player_space['x-index'] && $y === $player_space['y-index']);
                    $isAccessible = $accessible_coordinates->contains(fn($coord) => $coord['x'] === $x && $coord['y'] === $y);
                    $coordinate = chr(65 + $x) . ($y + 1); // Convert x,y to A1 format
                    $selectedValue = $this->round_properties[\App\Modifiers\Classes\FarmMap::key()][$element['property_name']] ?? null;
                    $isSelected = $selectedValue === $coordinate;

                    $bgColor = $isPlayerSpace ? 'background-color: #fef3c7;' : ($isSelected ? 'background-color: #93c5fd;' : ($isAccessible ? 'background-color: #e5e7eb;' : ''));
                    $border = $isSelected ? 'border: 3px solid #3b82f6;' : 'border: 1px solid #ccc;';
                    $cursor = $isAccessible ? 'cursor: pointer;' : '';
                @endphp
                <div
                    style="{{ $border }} padding: 1px; min-height: 10px; display: flex; align-items: center; justify-content: center; {{ $bgColor }} {{ $cursor }}"
                    @if($isAccessible)
                        wire:click="$set('round_properties.{{ \App\Modifiers\Classes\FarmMap::key() }}.{{ $element['property_name'] }}', '{{ $coordinate }}')"
                    @endif
                >
                    @if(isset($grid[$y][$x]))
                        @if($isPlayerSpace)
                            <flux:icon.user variant="micro"/>
                        @endif
                    @else
                        <div style="color: #999;">Empty</div>
                    @endif
                </div>
            @endfor
        @endfor
    </div>
    <div class="flex flex-wrap gap-2 mt-4 justify-end">
        @php
            $selectedValue = $this->round_properties[\App\Modifiers\Classes\FarmMap::key()][$element['property_name']] ?? null;
        @endphp
        <x-button
            wire:loading.attr="disabled"
            wire:key="button-{{ \App\Modifiers\Classes\FarmMap::key() }}-move"
            variant="primary"
            wire:click="callClassAction('move', 'modifier', '{{ \App\Modifiers\Classes\FarmMap::key() }}', null)"
            :disabled="empty($selectedValue)"
        >
            💪 Move
        </x-button>

        @if ($element['can_build_road'])
            <x-button
                wire:loading.attr="disabled"
                wire:key="button-{{ \App\Modifiers\Classes\FarmMap::key() }}-build-road"
                variant="primary"
                wire:click="callClassAction('buildRoad', 'modifier', '{{ \App\Modifiers\Classes\FarmMap::key() }}', null)"
            >
                💪 Build Road
            </x-button>
        @endif
    </div>
</div>