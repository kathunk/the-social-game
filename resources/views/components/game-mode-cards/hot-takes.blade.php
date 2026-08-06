@props(['modes'])

@php
    // Hot Takes is a single-mode family. Just take the first one.
    $mode = $modes->first();
    if (! $mode) {
        return;
    }
@endphp

@once
    <script>
        // The mini tier list preview keeps re-ranking itself: every beat two
        // rows trade places, the snack chips gliding between tiers.
        window.hotTakesPreview = function () {
            const ROW_PITCH = 22; // row height + gap, px

            return {
                items: [
                    { id: 1, label: 'Hot Cheetos', row: 0 },
                    { id: 2, label: 'Trail mix', row: 1 },
                    { id: 3, label: 'Sour gummies', row: 2 },
                    { id: 4, label: 'Apple slices', row: 3 },
                    { id: 5, label: 'Rice cake', row: 4 },
                ],
                timer: null,

                init() {
                    if (! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        this.timer = setInterval(() => this.swapTwo(), 1500);
                    }
                },

                swapTwo() {
                    const a = Math.floor(Math.random() * this.items.length);
                    let b = Math.floor(Math.random() * this.items.length);
                    if (a === b) b = (b + 1) % this.items.length;
                    const rowA = this.items[a].row;
                    this.items[a].row = this.items[b].row;
                    this.items[b].row = rowA;
                },

                styleFor(item) {
                    return `transform: translateY(${item.row * ROW_PITCH}px);`;
                },
            };
        };
    </script>
@endonce

<div class="rounded-2xl bg-bold-orange text-pale p-5 shadow-sm overflow-hidden relative">
    <div class="grid grid-cols-[1fr_auto] gap-4 items-start">
        <div class="min-w-0">
            <h3 class="font-bold text-xl mb-1">{{ $mode->name }}</h3>
            <p class="text-xs opacity-90 leading-relaxed mb-3">
                Build your perfect tier list, then guess how your friends ranked theirs.
            </p>

            <div class="flex gap-1.5 flex-wrap">
                <span class="text-[10px] font-semibold bg-white/15 rounded-full px-2 py-0.5">2–10 players</span>
                <span class="text-[10px] font-semibold bg-white/15 rounded-full px-2 py-0.5">~15 min</span>
                @if (! $mode->is_public)
                    <span class="text-[10px] font-bold uppercase tracking-wide bg-white/20 rounded-full px-2 py-0.5">
                        Hidden · admin only
                    </span>
                @endif
            </div>
        </div>

        {{-- Mini tier list mockup: fixed tier letters, snack chips that keep
             trading places. Visible at every width — on narrow screens the
             description column compresses and wraps beside it. --}}
        <div class="w-28 sm:w-32 shrink-0">
            <div class="rounded-lg p-1.5 bg-gradient-to-b from-green-200 via-yellow-100 to-red-100" x-data="hotTakesPreview()">
                <div class="relative" style="height: 106px;">
                    @foreach (['A', 'B', 'C', 'D', 'F'] as $index => $letter)
                        <span
                            class="absolute left-0 inline-flex items-center justify-center w-3.5 h-3.5 rounded text-[7px] font-bold bg-zinc-800 text-white"
                            style="top: {{ $index * 22 + 3 }}px;"
                        >{{ $letter }}</span>
                    @endforeach

                    <template x-for="item in items" :key="item.id">
                        <div
                            class="absolute left-5 right-0 top-0 h-5 px-1.5 flex items-center bg-white/70 rounded transition-transform duration-500 ease-in-out"
                            :style="styleFor(item)"
                        >
                            <span class="text-[9px] font-medium text-zinc-800 truncate" x-text="item.label"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <button
        wire:click="startGameFromMode('{{ $mode->id }}')"
        wire:loading.attr="disabled"
        class="mt-4 w-full rounded-lg bg-white text-bold-orange font-bold py-2.5 px-4 text-sm hover:scale-[1.02] transition-transform"
    >
        Start a game
    </button>
</div>
