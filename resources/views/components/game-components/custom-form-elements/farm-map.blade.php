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
                <div style="border: 1px solid #ccc; padding: 1px; min-height: 10px; display: flex; align-items: center; justify-content: center;">
                    @if(isset($grid[$y][$x]))
                        @if($x === $player_space['x-index'] && $y === $player_space['y-index'])
                            <flux:icon.user variant="micro"/>
                        @endif
                    @else
                        <div style="color: #999;">Empty</div>
                    @endif
                </div>
            @endfor
        @endfor
    </div>
</div>