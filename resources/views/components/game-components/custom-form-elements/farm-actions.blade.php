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
        if (!function_exists('anchorOffset')) {
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
        }

        $skills_modifier_id = $this->player->game->modifiers->firstWhere('class_key', \App\Modifiers\Classes\FarmSkills::key())->id;
        $teams_modifier_id = $this->player->game->modifiers->firstWhere('class_key', \App\Modifiers\Classes\FarmTeams::key())->id;
        $map_modifier_id = $this->player->game->modifiers->firstWhere('class_key', \App\Modifiers\Classes\FarmMap::key())->id;
    @endphp

    {{-- Space Information --}}
    <div class="mb-4">
        <div class="mt-4 flex justify-between">
            <div class="flex flex-col gap-1">
                <x-subheading>💪 Actions available : {{ $element['actions']['actions'] }} / {{ $element['limit'] }}</x-subheading>
                <x-subheading>🌾 Grain sack: {{ $element['actions']['grain'] }} / {{ $element['actions']['grain_capacity'] }}</x-subheading>
            </div>
            <div class="flex flex-col gap-1">
                <flux:link variant="ghost" class="text-sm" href="{{ route('games.mods', ['game' => $this->player->game, 'modifier' => $skills_modifier_id]) }}">Upgrade skills</flux:link>
                <flux:link variant="ghost" class="text-sm" href="{{ route('games.mods', ['game' => $this->player->game, 'modifier' => $teams_modifier_id]) }}">Manage team</flux:link>
            </div>
        </div>
        @if(collect($element['player_space']['player_ids'])->count() > 1)
            <div class="mt-4"></div>
            <x-heading class="mt-4">Other players on your space:</x-heading>
            <x-subheading>
                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Team</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach (collect($element['player_space']['player_ids'])->reject(fn ($id) => $id === $this->player->id) as $id)
                            @php
                                $player = App\Models\Player::find($id);
                                $team = App\Models\Player::find($id)->team;
                            @endphp
                            <flux:table.row>
                                <flux:table.cell>
                                    <div class="flex items-center gap-1">
                                        {{ $player->name }}
                                        @if(collect($element['leader_ids'])->contains($id))
                                            <x-icons.crown class="text-yellow-500 w-4 h-4" />
                                        @endif
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex items-center gap-1">
                                        {{ $team->name ?? 'No team' }}
                                        @if(collect($element['leader_ids'])->contains($id))
                                            <flux:icon.user-group class="text-green-500 w-4 h-4" />
                                        @endif
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </x-subheading>
        @endif
    </div>

    <flux:separator />

    <div class="mt-4 mb-2 flex justify-between">
        <x-heading>You are on {{ chr(65 + $element['player_space']['x-index']) }}{{ $element['player_space']['y-index'] + 1 }} ({{ match($element['player_space']['type']) {
            'grass' => 'Grass',
            'desert' => 'Desert',
            'mountain' => 'Mountain',
            'swamp' => 'Swamp',
            'ash_heap' => 'Ash Heap',
            'fertile_ashland' => 'Fertile Ashland',
            'volcano' => 'Volcano',
        } }})</x-heading>
        @if ($element['can_see_history'])
            <flux:link variant="ghost" class="text-sm" href="{{ route('games.mods', ['game' => $this->player->game, 'modifier' => $map_modifier_id]) }}">See space history</flux:link>
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

        {{-- Overlays - Road rendered directly instead of using symbol to avoid transform issues --}}
        @if($overlays->contains('type', 'road'))
            @php
                $roadY = 890;
            @endphp
            <rect x="0" y="{{ $roadY }}" width="1600" height="80" fill="#555"/>
            <rect x="0" y="{{ $roadY + 30 }}" width="1600" height="20" fill="#d4a574"/>
            <rect x="0" y="{{ $roadY }}" width="1600" height="5" fill="#333"/>
            <rect x="0" y="{{ $roadY + 75 }}" width="1600" height="5" fill="#333"/>
        @endif

        {{-- Overlays --}}
        @foreach ($overlays as $i => $o)
            @php
                $type = $o['type'] ?? 'player';

                // Skip road since we render it directly above
                if ($type === 'road') {
                    continue;
                }
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

                // Optional color and style overrides
                $style = '';
                if (!empty($o['fill'])) $style .= "fill: {$o['fill']};";
                if (!empty($o['stroke'])) $style .= "stroke: {$o['stroke']};";
                if (isset($o['stroke-width'])) $style .= "stroke-width: {$o['stroke-width']};";
                if (isset($o['opacity'])) $style .= "opacity: {$o['opacity']};";
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
                <use href="#obj-{{ $type }}" transform="{{ $transform }}" style="{{ $style }}" />
            @endif
        @endforeach
    </svg>
</div>