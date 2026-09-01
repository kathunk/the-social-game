{{-- publicCta: renders signup/login links instead of the wire:click game
     starters, so the card can sit on the guest-facing /elephant page (which
     has no Livewire context and no modes to start) --}}
@props(['modes' => null, 'publicCta' => false])

@php
    // The elephant family has two modes: head-to-head (max 2 players) and
    // practice vs the bot (max 1). Either may be missing (e.g. mid-rollout) —
    // render whichever buttons we have modes for.
    $modes = $modes ?? collect();
    $versus = $modes->first(fn ($m) => $m->max_players !== 1);
    $bot = $modes->first(fn ($m) => $m->max_players === 1);
    $any = $versus ?? $bot;

    if (! $any && ! $publicCta) {
        return;
    }
@endphp

@once
    <style>
        @keyframes elephant-card-enter {
            from { transform: translate(var(--enter-x, 0), var(--enter-y, 0)); }
            to { transform: translate(0, 0); }
        }
    </style>
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

                    this.pushOnce();
                    this.timer = setInterval(() => {
                        // Hidden tabs get throttled timers and no paints —
                        // don't play beats nobody can see
                        if (! document.hidden) {
                            this.pushOnce();
                        }
                    }, 1000);
                },

                makeTile(c, r, enterX = 0, enterY = 0) {
                    return {
                        id: this.nextId++,
                        c: c,
                        r: r,
                        enterX: enterX,
                        enterY: enterY,
                        gone: false,
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

                    // The pushing tile claims the entry cell IMMEDIATELY, in
                    // the same tick as the run it shoved — board state is
                    // always consistent, so the next beat's run lookups can
                    // never see a half-applied push. Its visual slide-in is
                    // a pure CSS enter animation (see elephant-card-enter):
                    // no deferred JS, so throttled tabs can't corrupt state.
                    const cell = cellAt(0);
                    const enter = horizontal
                        ? [fromStart ? -this.pitchX : this.pitchX, 0]
                        : [0, fromStart ? -this.pitchY : this.pitchY];
                    this.tiles.push(this.makeTile(cell.c, cell.r, enter[0], enter[1]));
                },

                placeFor(tile) {
                    return `transform: translate(${tile.c * this.pitchX}px, ${tile.r * this.pitchY}px);`;
                },

                innerFor(tile) {
                    let style = `background-color: ${tile.color};`;

                    if (tile.enterX || tile.enterY) {
                        style += ` --enter-x: ${tile.enterX}px; --enter-y: ${tile.enterY}px; animation: elephant-card-enter 500ms ease-in-out;`;
                    }

                    return style;
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
            {{-- Outer layer: grid placement (transitions on push). Inner
                 layer: the tile itself, with its one-shot enter animation. --}}
            <div
                class="absolute transition-transform duration-500 ease-in-out"
                :style="placeFor(tile)"
            >
                <div class="w-12 h-12 rounded-lg" :style="innerFor(tile)"></div>
            </div>
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
            @if ($publicCta)
                @auth
                    <a
                        href="{{ route('dashboard') }}"
                        class="flex-1 text-center rounded-xl text-white font-bold py-2.5 px-4 text-sm hover:scale-[1.02] transition-transform"
                        style="background-color: #007393;"
                    >Play now</a>
                @else
                    <a
                        href="{{ route('register') }}"
                        class="flex-1 text-center rounded-xl text-white font-bold py-2.5 px-4 text-sm hover:scale-[1.02] transition-transform"
                        style="background-color: #FF6857;"
                    >Sign up to play</a>
                    <a
                        href="{{ route('login') }}"
                        class="flex-1 text-center rounded-xl text-white font-bold py-2.5 px-4 text-sm hover:scale-[1.02] transition-transform bg-white/10"
                    >Log in</a>
                @endauth
            @endif
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
