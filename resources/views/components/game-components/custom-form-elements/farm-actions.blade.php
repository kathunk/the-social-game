@props(['element'])

<div>
    {{-- Include sprite definitions --}}
    @include('components.game-components.custom-form-elements.farm-space')

    @php
        // Get sprite config from element data
        $config = $element['sprite_config'] ?? [
            'viewBox' => [0, 0, 1000, 1000],
            'background' => 'grass',
            'overlays' => []
        ];

        [$minX, $minY, $vbWidth, $vbHeight] = $config['viewBox'] ?? [0, 0, 1000, 1000];

        // Sort overlays by z (and optional y for faux depth)
        $overlays = collect($config['overlays'] ?? [])
            ->sortBy([
                ['z', 'asc'],
                ['y', 'asc'],
            ])->values();

        // Small helper: translate anchor keyword to local offsets (for 100x100 symbols)
        function anchorOffset($anchor) {
            return match($anchor) {
                'center' => [-50, -50],
                'bottom' => [-50, -100],
                'top'    => [-50, 0],
                'top-left' => [0, 0],
                'bottom-left' => [0, -100],
                'top-right' => [-100, 0],
                'bottom-right' => [-100, -100],
                'left' => [0, -50],
                'right' => [-100, -50],
                default => [-50, -50],
            };
        }
    @endphp

    {{-- Space Information --}}
    <div class="mb-4">
        <x-heading>
            You are in space {{ chr(65 + $element['player_space']['x-index']) }}{{ $element['player_space']['y-index'] + 1 }}: {{ $element['player_space']['type'] }}
        </x-heading>
        @if(collect($element['player_space']['player_ids'])->count() > 1)
            <x-subheading>
                Other players: {{ implode(', ', collect($element['player_space']['player_ids'])->reject(fn ($id) => $id === $this->player->id)->map(fn ($id) => App\Models\Player::find($id)->name . ' (' . App\Models\Player::find($id)->team->name . ')')->toArray()) }}
            </x-subheading>
        @endif
    </div>

    {{-- SVG Map Display --}}
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="{{ $minX }} {{ $minY }} {{ $vbWidth }} {{ $vbHeight }}"
        preserveAspectRatio="xMidYMid meet"
        role="img"
        aria-label="Farm Map"
        class="w-full h-auto border border-gray-300 rounded-lg"
    >
        {{-- Background --}}
        <use href="#bg-{{ $config['background'] ?? 'grass' }}" />

        {{-- Debug: Show overlay count --}}
        <!-- Overlays count: {{ count($overlays) }} -->

        {{-- Overlays --}}
        @foreach ($overlays as $i => $o)
            @php
                $type = $o['type'] ?? 'player';
                $x = $o['x'] ?? 0;
                $y = $o['y'] ?? 0;
                $rotate = $o['rotate'] ?? 0;

                // scale handling: prefer scaleX/scaleY over uniform scale if present
                $sx = $o['scaleX'] ?? ($o['scale'] ?? 1);
                $sy = $o['scaleY'] ?? ($o['scale'] ?? 1);

                // anchor offset in local symbol coords
                [$ax, $ay] = anchorOffset($o['anchor'] ?? 'center');

                // Compose transform so that (x,y) is the anchored point
                // 1) move local origin by anchor offsets
                // 2) scale in local space
                // 3) rotate around 0,0
                // 4) translate to x,y in map space
                $transform = "translate({$x} {$y}) rotate({$rotate}) scale({$sx} {$sy}) translate({$ax} {$ay})";

                // Optional color override
                $style = '';
                if (!empty($o['fill'])) $style .= "fill: {$o['fill']};";
                if (!empty($o['stroke'])) $style .= "stroke: {$o['stroke']};";
                if (!empty($o['opacity'])) $style .= "opacity: {$o['opacity']};";
            @endphp

            @if($type === 'text')
                <!-- Text overlay: {{ $o['text'] ?? '' }} -->
                <text
                    x="{{ $x }}"
                    y="{{ $y }}"
                    font-size="{{ $o['font-size'] ?? 12 }}"
                    fill="{{ $o['fill'] ?? '#000' }}"
                    text-anchor="{{ $o['text-anchor'] ?? 'middle' }}"
                    dominant-baseline="{{ $o['dominant-baseline'] ?? 'middle' }}"
                >{{ $o['text'] ?? '' }}</text>
            @else
                <!-- Overlay {{ $i }}: type={{ $type }}, href=#obj-{{ $type }}, transform={{ $transform }} -->
                <use href="#obj-{{ $type }}" transform="{{ $transform }}" style="{{ $style }}" stroke-width="0" />
            @endif
        @endforeach
    </svg>

    {{-- Actions Information --}}
    <div class="mt-4">
        <x-subheading>Actions: {{ $element['actions']['actions'] }} / {{ $element['limit'] }} (buttons with 💪 cost 1 action)</x-subheading>
        <x-subheading>Grain: {{ $element['actions']['grain'] }} / {{ $element['actions']['grain_capacity'] }}</x-subheading>
    </div>
</div>