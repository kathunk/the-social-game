@props(['modes'])

@php
    // Hot Takes is a single-mode family. Just take the first one.
    $mode = $modes->first();
    if (! $mode) {
        return;
    }
@endphp

<div class="rounded-2xl bg-bold-orange text-pale p-5 shadow-sm overflow-hidden relative">
    <div class="grid grid-cols-[1fr_auto] gap-4 items-center">
        <div class="min-w-0">
            <h3 class="font-bold text-xl mb-1">{{ $mode->name }}</h3>
            <p class="text-xs opacity-90 leading-relaxed mb-3">
                Build your perfect tier list, then guess how your friends ranked theirs.
            </p>

            <div class="flex gap-1.5 flex-wrap mb-4">
                <span class="text-[10px] font-semibold bg-white/15 rounded-full px-2 py-0.5">2–10 players</span>
                <span class="text-[10px] font-semibold bg-white/15 rounded-full px-2 py-0.5">~15 min</span>
                @if (! $mode->is_public)
                    <span class="text-[10px] font-bold uppercase tracking-wide bg-white/20 rounded-full px-2 py-0.5">
                        Hidden · admin only
                    </span>
                @endif
            </div>

            <button
                wire:click="startGameFromMode('{{ $mode->id }}')"
                wire:loading.attr="disabled"
                class="rounded-lg bg-white text-bold-orange font-bold py-2 px-4 text-sm hover:scale-[1.02] transition-transform"
            >
                Start a game
            </button>
        </div>

        {{-- Mini tier list mockup --}}
        <div class="hidden sm:block w-32 shrink-0">
            <div class="rounded-lg p-1.5 bg-gradient-to-b from-green-200 via-yellow-100 to-red-100">
                <ul class="flex flex-col gap-1">
                    @php
                        $miniTiers = [
                            ['letter' => 'A', 'value' => 'Hot Cheetos'],
                            ['letter' => 'B', 'value' => 'Trail mix'],
                            ['letter' => 'C', 'value' => 'Sour gummies'],
                            ['letter' => 'D', 'value' => 'Apple slices'],
                            ['letter' => 'F', 'value' => 'Rice cake'],
                        ];
                    @endphp
                    @foreach ($miniTiers as $tier)
                        <li class="w-full px-1.5 py-1 text-[9px] flex flex-row items-center gap-1 bg-white/70 rounded">
                            <span class="inline-flex items-center justify-center w-3.5 h-3.5 rounded text-[7px] font-bold bg-zinc-800 text-white shrink-0">{{ $tier['letter'] }}</span>
                            <span class="font-medium text-zinc-800 truncate">{{ $tier['value'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
