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
    <script>
        // Background animation for the elephant card: a loose grid of
        // game-colored tiles where, every beat, a random row or column
        // slides one cell — up, down, left, or right — with the same snappy
        // ease as slides on the real board. Tiles pushed past the edge slide
        // out (the card clips them) and a fresh tile slides in behind them.
        window.elephantCardTiles = function () {
            const PITCH = 36; // 28px tile + 8px gap
            const COLORS = ['#FF6857', '#007393'];

            return {
                tiles: [],
                nextId: 1,
                cols: 0,
                rows: 0,
                timer: null,

                init() {
                    const width = this.$el.offsetWidth || 320;
                    const height = this.$el.offsetHeight || 170;
                    this.cols = Math.ceil(width / PITCH) + 1;
                    this.rows = Math.ceil(height / PITCH) + 1;

                    for (let r = 0; r < this.rows; r++) {
                        for (let c = 0; c < this.cols; c++) {
                            if (Math.random() < 0.45) {
                                this.tiles.push(this.makeTile(c, r));
                            }
                        }
                    }

                    if (! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        this.timer = setInterval(() => this.slideOnce(), 1000);
                    }
                },

                makeTile(c, r) {
                    return {
                        id: this.nextId++,
                        c: c,
                        r: r,
                        color: COLORS[Math.floor(Math.random() * COLORS.length)],
                    };
                },

                slideOnce() {
                    const horizontal = Math.random() < 0.5;
                    const delta = Math.random() < 0.5 ? 1 : -1;

                    if (horizontal) {
                        const row = Math.floor(Math.random() * this.rows);
                        this.tiles.filter((t) => t.r === row).forEach((t) => { t.c += delta; });
                        this.cull();
                        // A fresh tile slides in from the entry edge
                        if (Math.random() < 0.6) {
                            const entry = this.makeTile(delta === 1 ? -1 : this.cols, row);
                            this.tiles.push(entry);
                            setTimeout(() => { entry.c += delta; }, 30);
                        }
                    } else {
                        const col = Math.floor(Math.random() * this.cols);
                        this.tiles.filter((t) => t.c === col).forEach((t) => { t.r += delta; });
                        this.cull();
                        if (Math.random() < 0.6) {
                            const entry = this.makeTile(col, delta === 1 ? -1 : this.rows);
                            this.tiles.push(entry);
                            setTimeout(() => { entry.r += delta; }, 30);
                        }
                    }
                },

                // Drop tiles that have fully slid past the clipped edge —
                // after their exit transition finishes
                cull() {
                    const gone = this.tiles.filter(
                        (t) => t.c < -1 || t.c > this.cols || t.r < -1 || t.r > this.rows
                    );
                    if (gone.length) {
                        setTimeout(() => {
                            this.tiles = this.tiles.filter((t) => ! gone.includes(t));
                        }, 500);
                    }
                },

                styleFor(tile) {
                    return `transform: translate(${tile.c * PITCH}px, ${tile.r * PITCH}px); background-color: ${tile.color};`;
                },
            };
        };
    </script>
@endonce

<div class="relative overflow-hidden rounded-2xl bg-slate-900 text-white p-5 shadow-sm">
    {{-- Sliding tile background: rows and columns snap one cell at a time,
         just like slides on the board --}}
    <div
        x-data="elephantCardTiles()"
        class="absolute inset-0 pointer-events-none opacity-30"
        aria-hidden="true"
    >
        <template x-for="tile in tiles" :key="tile.id">
            <div
                class="absolute w-7 h-7 rounded-md transition-transform duration-500 ease-in-out"
                :style="styleFor(tile)"
            ></div>
        </template>
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
            <span class="text-[10px] font-semibold bg-white/10 rounded-full px-2 py-0.5">~3 min</span>
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
