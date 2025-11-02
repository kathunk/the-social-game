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
                <div class="overflow-x-auto text-xs">
                    <flux:table class="w-full">
                        <flux:table.columns>
                            <flux:table.column class="whitespace-normal break-words">Name</flux:table.column>
                            <flux:table.column class="whitespace-normal break-words">Team</flux:table.column>
                            @if(collect($element['pickpocketable_opponents'])->count() > 0)
                                <flux:table.column class="whitespace-normal break-words"></flux:table.column>
                            @endif
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach (collect($element['player_space']['player_ids'])->reject(fn ($id) => $id === $this->player->id) as $id)
                                @php
                                    $other_player = App\Models\Player::find($id);
                                    $team = App\Models\Player::find($id)->team;
                                @endphp
                                <flux:table.row>
                                    <flux:table.cell class="whitespace-normal break-words align-top">
                                        <div class="flex items-start gap-1 flex-wrap">
                                            <span class="break-words text-xs">{{ $other_player->name }}</span>
                                            @if(collect($element['leader_ids'])->contains($id))
                                                <x-icons.crown class="text-yellow-500 w-4 h-4 flex-shrink-0" />
                                            @endif
                                        </div>
                                    </flux:table.cell>
                                        <flux:table.cell class="whitespace-normal break-words align-top">
                                            <div class="flex items-start gap-1 flex-wrap">
                                                <span class="break-words text-xs">{{ $team->name ?? 'No team' }}</span>
                                                @if($other_player->team_id === $this->player->team_id)
                                                    <flux:icon.user-group class="text-green-500 w-4 h-4 flex-shrink-0" />
                                                @endif
                                            </div>
                                        </flux:table.cell>
                                    @if($element['pickpocketable_opponents']->contains($other_player))
                                        @php
                                            $cost = 4 - $element['player_skills']['Thief'];
                                            $can_afford_to_pickpocket = $element['actions'] >= $cost;
                                            $cost_suffix = $cost > 0 ? '💪'.str_repeat('💪', $cost - 1) : '';
                                        @endphp
                                        <flux:table.cell class="whitespace-normal break-words align-top">
                                            <flux:button
                                                variant="ghost"
                                                size="xs"
                                                class="text-xs"
                                                wire:click="$set('round_properties.{{ \App\Modifiers\Classes\FarmActions::key() }}.pickpocket_target_id', '{{ $other_player->id }}'); $wire.callClassAction('pickpocketOpponent', 'modifier', '{{ \App\Modifiers\Classes\FarmActions::key() }}', null)"
                                                :disabled="!$can_afford_to_pickpocket"
                                            >
                                                {{ $cost_suffix }} Pickpocket
                                            </flux:button>
                                        </flux:table.cell>
                                    @endif
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
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
            'tunnel' => 'Secret Tunnel',
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

        {{-- Overlays - Wall rendered directly (behind everything) --}}
        @if($overlays->contains('type', 'wall'))
            @php
                $wallY = 700;
                $wallHeight = 120;
            @endphp
            {{-- Main wall body --}}
            <rect x="0" y="{{ $wallY }}" width="1600" height="{{ $wallHeight }}" fill="#808080"/>
            {{-- Stone texture lines --}}
            <rect x="0" y="{{ $wallY }}" width="1600" height="3" fill="#606060"/>
            <rect x="0" y="{{ $wallY + 30 }}" width="1600" height="2" fill="#606060"/>
            <rect x="0" y="{{ $wallY + 60 }}" width="1600" height="2" fill="#606060"/>
            <rect x="0" y="{{ $wallY + 90 }}" width="1600" height="2" fill="#606060"/>
            <rect x="0" y="{{ $wallY + $wallHeight - 3 }}" width="1600" height="3" fill="#606060"/>
            {{-- Highlights for stone texture --}}
            <rect x="0" y="{{ $wallY + 5 }}" width="1600" height="1" fill="#a0a0a0"/>
            <rect x="0" y="{{ $wallY + 35 }}" width="1600" height="1" fill="#a0a0a0"/>
            <rect x="0" y="{{ $wallY + 65 }}" width="1600" height="1" fill="#a0a0a0"/>
            <rect x="0" y="{{ $wallY + 95 }}" width="1600" height="1" fill="#a0a0a0"/>
        @endif

        {{-- Overlays - Watchtower rendered directly --}}
        @if($overlays->contains('type', 'watchtower'))
            @php
                $towerX = 10;
                $towerY = 325;
                $towerWidth = 150;
                $towerHeight = 375;
                $battlement = 20; // Height of battlements
            @endphp
            {{-- Main tower body --}}
            <rect x="{{ $towerX }}" y="{{ $towerY }}" width="{{ $towerWidth }}" height="{{ $towerHeight }}" fill="#707070" stroke="#505050" stroke-width="3"/>
            {{-- Stone texture lines --}}
            <rect x="{{ $towerX }}" y="{{ $towerY + 60 }}" width="{{ $towerWidth }}" height="2" fill="#505050"/>
            <rect x="{{ $towerX }}" y="{{ $towerY + 125 }}" width="{{ $towerWidth }}" height="2" fill="#505050"/>
            <rect x="{{ $towerX }}" y="{{ $towerY + 190 }}" width="{{ $towerWidth }}" height="2" fill="#505050"/>
            <rect x="{{ $towerX }}" y="{{ $towerY + 255 }}" width="{{ $towerWidth }}" height="2" fill="#505050"/>
            <rect x="{{ $towerX }}" y="{{ $towerY + 320 }}" width="{{ $towerWidth }}" height="2" fill="#505050"/>
            {{-- Battlements (crenellations) at top --}}
            <rect x="{{ $towerX }}" y="{{ $towerY - $battlement }}" width="30" height="{{ $battlement }}" fill="#707070" stroke="#505050" stroke-width="2"/>
            <rect x="{{ $towerX + 40 }}" y="{{ $towerY - $battlement }}" width="30" height="{{ $battlement }}" fill="#707070" stroke="#505050" stroke-width="2"/>
            <rect x="{{ $towerX + 80 }}" y="{{ $towerY - $battlement }}" width="30" height="{{ $battlement }}" fill="#707070" stroke="#505050" stroke-width="2"/>
            <rect x="{{ $towerX + 120 }}" y="{{ $towerY - $battlement }}" width="30" height="{{ $battlement }}" fill="#707070" stroke="#505050" stroke-width="2"/>
            {{-- Arrow slit windows --}}
            <rect x="{{ $towerX + 40 }}" y="{{ $towerY + 50 }}" width="5" height="30" fill="#202020"/>
            <rect x="{{ $towerX + 105 }}" y="{{ $towerY + 50 }}" width="5" height="30" fill="#202020"/>
            <rect x="{{ $towerX + 40 }}" y="{{ $towerY + 140 }}" width="5" height="30" fill="#202020"/>
            <rect x="{{ $towerX + 105 }}" y="{{ $towerY + 140 }}" width="5" height="30" fill="#202020"/>
            <rect x="{{ $towerX + 40 }}" y="{{ $towerY + 230 }}" width="5" height="30" fill="#202020"/>
            <rect x="{{ $towerX + 105 }}" y="{{ $towerY + 230 }}" width="5" height="30" fill="#202020"/>
        @endif

        {{-- Overlays - Road rendered directly instead of using symbol to avoid transform issues --}}
        @if($overlays->contains('type', 'road'))
            @php
                $roadY = 830;
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

                // Skip wall, watchtower, and road since we render them directly above
                if (in_array($type, ['wall', 'watchtower', 'road'])) {
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