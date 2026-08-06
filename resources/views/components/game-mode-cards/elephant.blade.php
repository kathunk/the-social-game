@props(['modes'])

@php
    // The elephant family has two modes: head-to-head (max 2 players) and
    // practice vs the bot (max 1). Either may be missing (e.g. mid-rollout) —
    // render whichever buttons we have modes for.
    $versus = $modes->first(fn ($m) => $m->max_players !== 1);
    $bot = $modes->first(fn ($m) => $m->max_players === 1);
    $any = $versus ?? $bot;

    if (! $any) {
        return;
    }
@endphp

@once
    <style>
        @keyframes elephant-card-drift-left {
            from { transform: translateX(0); }
            to { transform: translateX(-50%); }
        }
        @keyframes elephant-card-drift-right {
            from { transform: translateX(-50%); }
            to { transform: translateX(0); }
        }
        .elephant-card-row { animation: elephant-card-drift-left 36s linear infinite; }
        .elephant-card-row-reverse { animation: elephant-card-drift-right 44s linear infinite; }
        @media (prefers-reduced-motion: reduce) {
            .elephant-card-row, .elephant-card-row-reverse { animation: none; }
        }
    </style>
@endonce

<div class="relative overflow-hidden rounded-2xl bg-slate-900 text-white p-5 shadow-sm">
    {{-- Sliding tile background: two conveyor rows of game-colored tiles
         drifting in opposite directions, like slides on the board --}}
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        @php
            // Tile sequences use the real game colors (orange / teal) with
            // gaps for rhythm; each row is doubled so the -50% keyframe loops
            // seamlessly.
            $rows = [
                ['top' => '8%', 'class' => 'elephant-card-row', 'opacity' => '0.35', 'tiles' => ['o', null, 't', 'o', null, null, 't', null, 'o', 't', null, 'o']],
                ['top' => '44%', 'class' => 'elephant-card-row-reverse', 'opacity' => '0.25', 'tiles' => ['t', null, null, 'o', 't', null, 'o', null, null, 't', 'o', null]],
                ['top' => '78%', 'class' => 'elephant-card-row', 'opacity' => '0.3', 'tiles' => [null, 'o', 't', null, 'o', null, null, 't', 'o', null, 't', null]],
            ];
        @endphp
        @foreach ($rows as $row)
            <div
                class="absolute flex gap-2 {{ $row['class'] }}"
                style="top: {{ $row['top'] }}; width: max-content; opacity: {{ $row['opacity'] }};"
            >
                @foreach ([...$row['tiles'], ...$row['tiles']] as $tile)
                    <div
                        class="w-7 h-7 rounded-md shrink-0"
                        style="background-color: {{ match ($tile) {'o' => '#FF6857', 't' => '#007393', default => 'transparent'} }};"
                    ></div>
                @endforeach
            </div>
        @endforeach
    </div>

    <div class="relative">
        <div class="flex items-start justify-between gap-3 mb-3">
            <div>
                <h3 class="font-bold text-xl">Elephant in the Room</h3>
                <p class="text-xs opacity-80 leading-relaxed mt-1">
                    Slide tiles, block with the elephant, and be the first to make your shape.
                </p>
            </div>
            <div class="text-3xl shrink-0">🐘</div>
        </div>

        <div class="flex gap-1.5 flex-wrap mb-3">
            <span class="text-[10px] font-semibold bg-white/10 rounded-full px-2 py-0.5">1–2 players</span>
            <span class="text-[10px] font-semibold bg-white/10 rounded-full px-2 py-0.5">~10 min</span>
            @if ($any && ! $any->is_public)
                <span class="text-[10px] font-bold uppercase tracking-wide text-purple-200 bg-purple-900/50 rounded-full px-2 py-0.5">Hidden</span>
            @endif
        </div>

        <div class="flex flex-col sm:flex-row gap-2">
            @if ($versus)
                <button
                    wire:click="startGameFromMode('{{ $versus->id }}')"
                    wire:loading.attr="disabled"
                    class="flex-1 rounded-xl text-white font-bold py-2.5 px-4 text-sm hover:scale-[1.02] transition-transform"
                    style="background-color: #FF6857;"
                >
                    Challenge a friend
                </button>
            @endif
            @if ($bot)
                <button
                    wire:click="startGameFromMode('{{ $bot->id }}')"
                    wire:loading.attr="disabled"
                    class="flex-1 rounded-xl text-white font-bold py-2.5 px-4 text-sm hover:scale-[1.02] transition-transform"
                    style="background-color: #007393;"
                >
                    Practice vs the Bot
                </button>
            @endif
        </div>
    </div>
</div>
