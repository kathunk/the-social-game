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
        // Background animation for the elephant card, obeying the game's one
        // law of motion: nothing moves unless a tile pushes it. The board
        // starts EMPTY. Every beat, one new tile slides in from a random
        // edge; only the contiguous run of tiles in front of it shifts one
        // cell deeper (the real cascade rule). The card gradually fills, and
        // once a line is full the next push shoves the far tile off the
        // clipped edge — a true push, never a drift.
        window.elephantCardTiles = function () {
            const TARGET_PITCH = 56; // ideal grid cell: 48px tile + 8px gap
            const COLORS = ['#FF6857', '#007393'];

            return {
                tiles: [],
                nextId: 1,
                cols: 0,
                rows: 0,
                pitchX: TARGET_PITCH,
                pitchY: TARGET_PITCH,
                timer: null,

                init() {
                    const width = this.$el.offsetWidth || 320;
                    const height = this.$el.offsetHeight || 170;

                    // The grid spans the card EXACTLY, so every entry edge is
                    // a visible edge — tiles are always seen sliding in from
                    // just outside, never materializing in clipped dead space
                    this.cols = Math.max(3, Math.round(width / TARGET_PITCH));
                    this.rows = Math.max(2, Math.round(height / TARGET_PITCH));
                    this.pitchX = width / this.cols;
                    this.pitchY = height / this.rows;

                    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        // No animation: show the filled end-state instead
                        for (let r = 0; r < this.rows; r++) {
                            for (let c = 0; c < this.cols; c++) {
                                this.tiles.push(this.makeTile(c, r));
                            }
                        }

                        return;
                    }

                    this.timer = setInterval(() => this.pushOnce(), 1000);
                },

                makeTile(c, r) {
                    return {
                        id: this.nextId++,
                        c: c,
                        r: r,
                        color: COLORS[Math.floor(Math.random() * COLORS.length)],
                    };
                },

                pushOnce() {
                    const horizontal = Math.random() < 0.5;
                    const lineLength = horizontal ? this.cols : this.rows;
                    const lineIndex = Math.floor(Math.random() * (horizontal ? this.rows : this.cols));
                    const fromStart = Math.random() < 0.5; // left/top vs right/bottom entry

                    // Cell at depth i along the slide path (0 = the entry
                    // cell; -1 and lineLength resolve to off-board cells)
                    const cellAt = (i) => {
                        const pos = fromStart ? i : lineLength - 1 - i;

                        return horizontal ? { c: pos, r: lineIndex } : { c: lineIndex, r: pos };
                    };

                    const tileAt = (i) => {
                        const cell = cellAt(i);

                        return this.tiles.find((t) => ! t.gone && t.c === cell.c && t.r === cell.r);
                    };

                    // The contiguous occupied run in front of the entry —
                    // the only tiles the new tile actually pushes
                    let run = 0;
                    while (run < lineLength && tileAt(run)) run++;

                    // Shift the run one cell deeper, deepest first. A full
                    // line means the far tile is pushed off the board.
                    for (let i = run - 1; i >= 0; i--) {
                        const tile = tileAt(i);
                        const target = cellAt(i + 1);
                        tile.c = target.c;
                        tile.r = target.r;

                        if (i + 1 >= lineLength) {
                            tile.gone = true;
                            setTimeout(() => {
                                this.tiles = this.tiles.filter((t) => t !== tile);
                            }, 550);
                        }
                    }

                    // The pushing tile: spawn just off the entry edge, then
                    // slide onto the board in the same beat as the run it
                    // pushes (double-rAF so the off-edge position paints
                    // before the transition starts)
                    const spawn = cellAt(-1);
                    const entry = this.makeTile(spawn.c, spawn.r);
                    this.tiles.push(entry);
                    const target = cellAt(0);
                    requestAnimationFrame(() => requestAnimationFrame(() => {
                        entry.c = target.c;
                        entry.r = target.r;
                    }));
                },

                styleFor(tile) {
                    return `transform: translate(${tile.c * this.pitchX}px, ${tile.r * this.pitchY}px); background-color: ${tile.color};`;
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
                class="absolute w-12 h-12 rounded-lg transition-transform duration-500 ease-in-out"
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
