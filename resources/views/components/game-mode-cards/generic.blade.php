@props(['modes'])

@php
    $mode = $modes->first();
    if (! $mode) {
        return;
    }

    $playerRange = match (true) {
        $mode->min_players && $mode->max_players => "{$mode->min_players}–{$mode->max_players} players",
        $mode->min_players                        => "{$mode->min_players}+ players",
        $mode->max_players                        => "Up to {$mode->max_players} players",
        default                                   => 'Any size',
    };
@endphp

<div class="rounded-2xl border border-zinc-200 bg-white p-5 flex flex-col gap-3 shadow-sm">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <h3 class="font-bold text-base text-zinc-900 truncate">{{ $mode->name }}</h3>
            @if (! $mode->is_public)
                <span class="inline-block text-[10px] font-bold uppercase tracking-wide text-purple-700 bg-purple-100 rounded-full px-2 py-0.5 mt-1">
                    Hidden · admin only
                </span>
            @endif
        </div>
    </div>

    @if ($mode->description)
        <p class="text-xs text-zinc-600 leading-relaxed line-clamp-3">{{ $mode->description }}</p>
    @endif

    <div class="flex gap-1.5 flex-wrap">
        <span class="text-[10px] font-semibold bg-zinc-100 text-zinc-700 rounded-full px-2 py-0.5">{{ $playerRange }}</span>
        <span class="text-[10px] font-semibold bg-zinc-100 text-zinc-700 rounded-full px-2 py-0.5">{{ ucfirst($mode->type) }}</span>
    </div>

    <button
        wire:click="startGameFromMode('{{ $mode->id }}')"
        wire:loading.attr="disabled"
        class="mt-1 w-full rounded-lg bg-bold-orange text-white font-bold py-2.5 px-4 text-sm hover:scale-[1.02] transition-transform"
    >
        Start a game
    </button>
</div>
