@props(['modes'])

@php
    if ($modes->isEmpty()) {
        return;
    }

    // Sort variants in a stable order with the canonical "Pecking Order" first.
    $sorted = $modes->sortBy(function ($mode) {
        $name = mb_strtolower($mode->name);
        return match (true) {
            str_contains($name, 'pecking')      => 0,
            str_contains($name, 'blood')        => 1,
            str_contains($name, 'king maker')   => 2,
            str_contains($name, 'pyramid')      => 3,
            default                              => 9,
        };
    })->values();

    // Variant descriptions and emoji
    $variantMeta = [
        'pecking' => ['emoji' => '🐔', 'tagline' => 'The classic. Vote your friends up or down. Predict the votes for hidden points.'],
        'blood'   => ['emoji' => '🩸', 'tagline' => 'Secretly ally with one player. Sniff out other alliances to score.'],
        'king'    => ['emoji' => '👑', 'tagline' => 'Resign early to give your points away. Anoint a king or nuke your enemies.'],
        'pyramid' => ['emoji' => '🔺', 'tagline' => 'Recruit, refer, get in early. A pyramid scheme that can\'t go wrong.'],
    ];

    $metaFor = function ($mode) use ($variantMeta) {
        $name = mb_strtolower($mode->name);
        foreach ($variantMeta as $key => $meta) {
            if (str_contains($name, $key)) {
                return $meta;
            }
        }
        return ['emoji' => '🎯', 'tagline' => $mode->description ?? ''];
    };
@endphp

<div class="rounded-2xl bg-zinc-900 text-pale p-5 shadow-sm">
    <div class="flex items-start justify-between gap-3 mb-3">
        <div>
            <h3 class="font-bold text-xl">Pecking Order</h3>
            <p class="text-xs opacity-80 leading-relaxed mt-1">
                A popularity contest for the truly devious. Pick a variant.
            </p>
        </div>
        <div class="text-2xl shrink-0">🐔</div>
    </div>

    <div class="flex gap-1.5 flex-wrap mb-3">
        <span class="text-[10px] font-semibold bg-white/10 rounded-full px-2 py-0.5">4–12 players</span>
        <span class="text-[10px] font-semibold bg-white/10 rounded-full px-2 py-0.5">~30 min</span>
    </div>

    <div class="space-y-2">
        @foreach ($sorted as $mode)
            @php $meta = $metaFor($mode); @endphp
            <button
                wire:click="startGameFromMode('{{ $mode->id }}')"
                wire:loading.attr="disabled"
                class="w-full text-left rounded-xl border border-white/15 hover:border-white/40 hover:bg-white/5 px-3 py-2.5 transition-colors group"
            >
                <div class="flex items-start gap-3">
                    <div class="text-xl leading-none shrink-0 mt-0.5">{{ $meta['emoji'] }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <div class="font-bold text-sm">{{ $mode->name }}</div>
                            @if (! $mode->is_public)
                                <span class="text-[9px] font-bold uppercase tracking-wide text-purple-200 bg-purple-900/50 rounded-full px-2 py-0.5">
                                    Hidden
                                </span>
                            @endif
                        </div>
                        <div class="text-[11px] opacity-70 leading-snug mt-0.5">{{ $meta['tagline'] }}</div>
                    </div>
                    <div class="text-pale/60 group-hover:text-pale transition-colors shrink-0 mt-0.5">→</div>
                </div>
            </button>
        @endforeach
    </div>
</div>
