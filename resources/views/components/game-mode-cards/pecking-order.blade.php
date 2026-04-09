@props(['modes'])

@php
    if ($modes->isEmpty()) {
        return;
    }

    // Resolve variant metadata for each mode via the registry, then sort by
    // the variant's declared display order.
    $modesWithMeta = $modes
        ->map(fn ($mode) => [
            'mode' => $mode,
            'meta' => \App\Support\GameModeCardRegistry::variantMetaForMode('pecking-order', $mode),
        ])
        ->sortBy(fn ($entry) => $entry['meta']['sort'])
        ->values();
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
        @foreach ($modesWithMeta as $entry)
            @php
                $mode = $entry['mode'];
                $meta = $entry['meta'];
            @endphp
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
