@props(['element'])

@once
    <style>
        @keyframes elephant-victory-glow {
            0%, 100% { box-shadow: 0 0 4px 2px rgba(250, 204, 21, 0.7); }
            50% { box-shadow: 0 0 14px 6px rgba(250, 204, 21, 0.95); }
        }
        .elephant-victory-glow { animation: elephant-victory-glow 1.2s ease-in-out infinite; }
    </style>
    <script>
        // ─── Pure rules engine — a 1:1 mirror of Support/BoardLogic.php. ───
        // Used for optimistic animation of every move and as the bot's brain
        // in single-player games. The server independently validates every
        // move via Verbs events, so a divergence here can't corrupt a match.
        window.ElephantEngine = {
            SLIDING_POSITIONS: {
                1: { down: [1, 5, 9, 13], right: [1, 2, 3, 4] },
                2: { down: [2, 6, 10, 14] },
                3: { down: [3, 7, 11, 15] },
                4: { down: [4, 8, 12, 16], left: [4, 3, 2, 1] },
                5: { right: [5, 6, 7, 8] },
                8: { left: [8, 7, 6, 5] },
                9: { right: [9, 10, 11, 12] },
                12: { left: [12, 11, 10, 9] },
                13: { right: [13, 14, 15, 16], up: [13, 9, 5, 1] },
                14: { up: [14, 10, 6, 2] },
                15: { up: [15, 11, 7, 3] },
                16: { up: [16, 12, 8, 4], left: [16, 15, 14, 13] },
            },

            ADJACENT: {
                1: [2, 5], 2: [1, 3, 6], 3: [2, 4, 7], 4: [3, 8],
                5: [1, 6, 9], 6: [2, 5, 7, 10], 7: [3, 6, 8, 11], 8: [4, 7, 12],
                9: [5, 10, 13], 10: [6, 9, 11, 14], 11: [7, 10, 12, 15], 12: [8, 11, 16],
                13: [9, 14], 14: [10, 13, 15], 15: [11, 14, 16], 16: [12, 15],
            },

            VICTORIES: {
                square: [
                    [1,2,5,6],[2,3,6,7],[3,4,7,8],[5,6,9,10],[6,7,10,11],[7,8,11,12],
                    [9,10,13,14],[10,11,14,15],[11,12,15,16],
                ],
                line: [
                    [1,5,9,13],[2,6,10,14],[3,7,11,15],[4,8,12,16],
                    [1,2,3,4],[5,6,7,8],[9,10,11,12],[13,14,15,16],
                ],
                el: [
                    [1,2,3,7],[2,3,4,8],[5,6,7,11],[6,7,8,12],[9,10,11,15],[10,11,12,16],
                    [1,5,6,7],[2,6,7,8],[5,9,10,11],[6,10,11,12],[9,13,14,15],[10,14,15,16],
                    [3,5,6,7],[4,6,7,8],[7,9,10,11],[8,10,11,12],[11,13,14,15],[12,14,15,16],
                    [1,2,3,5],[2,3,4,6],[5,6,7,9],[6,7,8,10],[9,10,11,13],[10,11,12,14],
                    [1,2,5,9],[2,3,6,10],[3,4,7,11],[5,6,9,13],[6,7,10,14],[7,8,11,15],
                    [1,2,6,10],[2,3,7,11],[3,4,8,12],[5,6,10,14],[6,7,11,15],[7,8,12,16],
                    [1,5,9,10],[2,6,10,11],[3,7,11,12],[5,9,13,14],[6,10,14,15],[7,11,15,16],
                    [2,6,9,10],[3,7,10,11],[4,8,11,12],[6,10,13,14],[7,11,14,15],[8,12,15,16],
                ],
                pyramid: [
                    [1,2,3,6],[2,3,4,7],[5,6,7,10],[6,7,8,11],[9,10,11,14],[10,11,12,15],
                    [2,5,6,7],[3,6,7,8],[6,9,10,11],[7,10,11,12],[10,13,14,15],[11,14,15,16],
                    [1,5,6,9],[2,6,7,10],[3,7,8,11],[5,9,10,13],[6,10,11,14],[7,11,12,15],
                    [2,5,6,10],[3,6,7,11],[4,7,8,12],[6,9,10,14],[7,10,11,15],[8,11,12,16],
                ],
                zig: [
                    [1,2,6,7],[2,3,7,8],[5,6,10,11],[6,7,11,12],[9,10,14,15],[10,11,15,16],
                    [2,3,5,6],[3,4,6,7],[6,7,9,10],[7,8,10,11],[10,11,13,14],[11,12,14,15],
                    [1,5,6,10],[2,6,7,11],[3,7,8,12],[5,9,10,14],[6,10,11,15],[7,11,12,16],
                    [2,5,6,9],[3,6,7,10],[4,7,8,11],[6,9,10,13],[7,10,11,14],[8,11,12,15],
                ],
            },

            DIR_VECTORS: {
                down: { x: 0, y: 1 },
                up: { x: 0, y: -1 },
                right: { x: 1, y: 0 },
                left: { x: -1, y: 0 },
            },

            slidingPositions(space, direction) {
                return this.SLIDING_POSITIONS[space][direction];
            },

            isSlideConfig(space, direction) {
                return !!(this.SLIDING_POSITIONS[space] && this.SLIDING_POSITIONS[space][direction]);
            },

            isBlocked(board, elephant, space, direction) {
                const [p1, p2, p3, p4] = this.slidingPositions(space, direction);
                if (elephant === p1) return true;
                if (board[p1] && elephant === p2) return true;
                if (board[p1] && board[p2] && elephant === p3) return true;
                if (board[p1] && board[p2] && board[p3] && elephant === p4) return true;
                return false;
            },

            validSlides(board, elephant) {
                const slides = [];
                for (const [space, dirs] of Object.entries(this.SLIDING_POSITIONS)) {
                    for (const direction of Object.keys(dirs)) {
                        if (!this.isBlocked(board, elephant, Number(space), direction)) {
                            slides.push({ space: Number(space), direction });
                        }
                    }
                }
                return slides;
            },

            applySlide(board, space, direction, actorId) {
                const [p1, p2, p3, p4] = this.slidingPositions(space, direction);
                const nb = { ...board };
                let pushedOffOwner = null;
                if (board[p1] && board[p2] && board[p3]) {
                    if (board[p4]) pushedOffOwner = board[p4];
                    nb[p4] = board[p3];
                }
                if (board[p1] && board[p2]) nb[p3] = board[p2];
                if (board[p1]) nb[p2] = board[p1];
                nb[p1] = actorId;
                return { board: nb, pushedOffOwner };
            },

            validElephantMoves(elephant) {
                return [...this.ADJACENT[elephant], elephant];
            },

            winningSpaces(board, actorId, shape) {
                for (const set of this.VICTORIES[shape]) {
                    if (set.every((s) => board[s] === actorId)) return set;
                }
                return [];
            },

            isVictorious(board, actorId, shape) {
                return this.winningSpaces(board, actorId, shape).length > 0;
            },

            // "Check" = one tile away from completing the shape: any victory
            // set with exactly 3 own tiles and the 4th space empty
            hasCheck(board, actorId, shape) {
                return this.VICTORIES[shape].some((set) => {
                    let own = 0, empty = 0;
                    for (const s of set) {
                        if (board[s] === actorId) own++;
                        else if (!board[s]) empty++;
                    }
                    return own === 3 && empty === 1;
                });
            },

            adjacencyCount(board, actorId) {
                let n = 0;
                for (let s = 1; s <= 16; s++) {
                    if (board[s] !== actorId) continue;
                    for (const a of this.ADJACENT[s]) {
                        if (board[a] === actorId) n++;
                    }
                }
                return n;
            },

            // Bot scoring, ported from the original BotLogic (hard difficulty:
            // always take the top-scoring slide; ties broken by the shuffle)
            scoreBoard(board, botId, humanId, shape) {
                let score = this.adjacencyCount(board, botId) - this.adjacencyCount(board, humanId);
                if (this.hasCheck(board, botId, shape)) score += 100;
                if (this.hasCheck(board, humanId, shape)) score -= 200;
                if (this.isVictorious(board, humanId, shape)) score -= 1000;
                if (this.isVictorious(board, botId, shape)) score += 1000000000;
                let botTiles = 0;
                for (let s = 1; s <= 16; s++) if (board[s] === botId) botTiles++;
                if (botTiles === 8) score -= 500;
                return score;
            },

            chooseBotSlide(board, elephant, botId, humanId, shape) {
                const slides = this.shuffle(this.validSlides(board, elephant));
                let best = null, bestScore = -Infinity;
                for (const slide of slides) {
                    const hb = this.applySlide(board, slide.space, slide.direction, botId).board;
                    const score = this.scoreBoard(hb, botId, humanId, shape);
                    if (score > bestScore) { bestScore = score; best = slide; }
                }
                return best;
            },

            chooseBotElephantMove(elephant) {
                const moves = this.validElephantMoves(elephant);
                return moves[Math.floor(Math.random() * moves.length)];
            },

            shuffle(arr) {
                const a = [...arr];
                for (let i = a.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [a[i], a[j]] = [a[j], a[i]];
                }
                return a;
            },
        };

        window.elephantBoard = function (configElId) {
            const cfg = JSON.parse(document.getElementById(configElId).textContent);
            const E = window.ElephantEngine;
            const CELL = 61; // 58px tile + gap, matches spaceToCoords() pitch

            return {
                // Authoritative-mirroring local model
                board: {}, elephant: 6, phase: 'tile', currentActorId: null,
                actorOrder: [], hands: {}, matchStatus: 'active',
                victorIds: [], winningSpaces: [], turnStartedAt: 0, lastSeq: 0,

                // Visual layer
                tiles: [], nextId: 1, initBoard: true,

                // Meta
                me: cfg.me, names: cfg.names, gameId: cfg.game_id,
                shape: cfg.state.victory_shape, isBotGame: cfg.state.is_bot_game,
                turnSeconds: cfg.turn_seconds, grace: cfg.forfeit_grace_seconds,

                // Optimistic bookkeeping
                sentMoveIds: [], pendingAction: false, animating: false,
                botThinking: false, queuedServerState: null, autoClaimedForTurn: null,
                timerNow: Math.floor(Date.now() / 1000), timerInterval: null,

                init() {
                    // The dashboard hard-refreshes the page on every
                    // GameUpdatedForReverb broadcast (fired at turn
                    // boundaries), so "receiving the opponent's move" usually
                    // means arriving here on a fresh page load. The snapshot
                    // persisted in localStorage tells us what this client has
                    // already seen: anything newer in the server's move log
                    // gets animated, and our own moves (already in the
                    // snapshot) never re-animate.
                    const snap = this.loadSnapshot();
                    if (snap && snap.lastSeq <= cfg.state.last_seq) {
                        this.restoreFrom(snap);
                        this.reconcile(cfg.state, cfg.moves);
                    } else {
                        this.snapTo(cfg.state);
                    }

                    this.$nextTick(() => setTimeout(() => { this.initBoard = false; }, 100));
                    this.timerInterval = setInterval(() => {
                        this.timerNow = Math.floor(Date.now() / 1000);
                        this.maybeAutoClaimForfeit();
                    }, 250);
                },

                // ── Getters ────────────────────────────────────────────────
                get opponentId() { return this.actorOrder.find((a) => a !== this.me); },
                get isMyTurn() { return this.matchStatus === 'active' && this.currentActorId === this.me; },
                get myTilePhase() { return this.isMyTurn && this.phase === 'tile' && !this.animating && !this.pendingAction && !this.botThinking; },
                get myElephantPhase() { return this.isMyTurn && this.phase === 'move' && !this.animating && !this.pendingAction && !this.botThinking; },
                get validSlideList() { return E.validSlides(this.board, this.elephant); },
                get validElephantMoveList() { return E.validElephantMoves(this.elephant); },
                get opponentOutOfTiles() { return (this.hands[this.opponentId] ?? 0) === 0; },
                get secondsLeft() { return Math.max(0, this.turnStartedAt + this.turnSeconds - this.timerNow); },
                get timerFraction() { return Math.min(100, Math.max(0, (1 - this.secondsLeft / this.turnSeconds) * 100)); },
                get timerUrgent() { return this.secondsLeft > 0 && this.secondsLeft <= 10; },
                get canClaimForfeit() {
                    return !this.isBotGame
                        && this.matchStatus === 'active'
                        && this.currentActorId !== this.me
                        && this.timerNow >= this.turnStartedAt + this.turnSeconds + this.grace;
                },
                colorFor(actorId) {
                    return actorId === this.actorOrder[0] ? '#FF6857' : '#007393';
                },

                isSlideValid(space, direction) {
                    return this.validSlideList.some((s) => s.space === space && s.direction === direction);
                },

                coords(space) {
                    const row = Math.floor((space - 1) / 4);
                    const col = (space - 1) % 4;
                    return { x: col * 60 + col, y: row * 60 + row };
                },

                uuid() {
                    return (window.crypto && crypto.randomUUID)
                        ? crypto.randomUUID()
                        : 'm-' + Date.now() + '-' + Math.random().toString(16).slice(2);
                },

                // ── Player interactions (optimistic: animate first, then send) ──
                playerSlide(space, direction) {
                    if (!this.myTilePhase || !this.isSlideValid(space, direction)) return;
                    const moveId = this.uuid();
                    this.sentMoveIds.push(moveId);
                    this.applyAndAnimateSlide(space, direction, this.me);
                    this.lastSeq++;
                    this.sendAction('slideTile', {
                        entry_space: space,
                        direction: direction,
                        client_move_id: moveId,
                    });
                },

                playerElephantMove(space) {
                    if (!this.myElephantPhase || !this.validElephantMoveList.includes(space)) return;
                    const moveId = this.uuid();
                    this.sentMoveIds.push(moveId);
                    this.applyElephantMove(space, this.me);
                    this.lastSeq++;
                    this.sendAction('moveElephant', {
                        to_space: space,
                        client_move_id: moveId,
                    });
                },

                // When the opponent's turn timer runs out, the waiting
                // player's client claims the win automatically — no prompt.
                // Latched per turn_started_at so a rejected claim (e.g. the
                // opponent's move raced in) doesn't retry until a new turn
                // starts a new clock.
                maybeAutoClaimForfeit() {
                    if (!this.canClaimForfeit || this.pendingAction) return;
                    if (this.autoClaimedForTurn === this.turnStartedAt) return;
                    this.autoClaimedForTurn = this.turnStartedAt;
                    this.sendAction('claimForfeit', {});
                },

                sendAction(action, props) {
                    this.pendingAction = true;
                    Object.entries(props).forEach(([key, value]) => {
                        this.$wire.set(`round_properties.{{ $element['class_key'] }}.${key}`, value, false);
                    });
                    this.$wire.callClassAction(action, 'challenge', '{{ $element['class_key'] }}', null)
                        .catch(() => {})
                        .finally(() => {
                            this.pendingAction = false;
                            if (this.queuedServerState) {
                                const q = this.queuedServerState;
                                this.queuedServerState = null;
                                this.reconcile(q.state, q.moves);
                            }
                            // Pull a fresh render so the sync bridge below fires
                            // with authoritative state — this is the rollback
                            // path when an optimistic move was rejected
                            this.$wire.$refresh();
                            this.maybeRunBot();
                        });
                },

                // ── The client-side bot (single-player games only) ─────────
                maybeRunBot() {
                    if (!this.isBotGame || this.matchStatus !== 'active') return;
                    if (this.currentActorId !== 'bot' || this.botThinking || this.pendingAction) return;

                    this.botThinking = true;

                    setTimeout(() => {
                        const slide = E.chooseBotSlide(this.board, this.elephant, 'bot', this.me, this.shape);
                        if (!slide) { this.botThinking = false; return; }

                        const tileMoveId = this.uuid();
                        this.sentMoveIds.push(tileMoveId);
                        this.applyAndAnimateSlide(slide.space, slide.direction, 'bot');
                        this.lastSeq++;

                        const completed = this.matchStatus === 'complete';
                        const props = {
                            bot_entry_space: slide.space,
                            bot_direction: slide.direction,
                            bot_tile_move_id: tileMoveId,
                        };

                        if (!completed) {
                            const elephantTo = E.chooseBotElephantMove(this.elephant);
                            const elephantMoveId = this.uuid();
                            this.sentMoveIds.push(elephantMoveId);
                            props.bot_to_space = elephantTo;
                            props.bot_elephant_move_id = elephantMoveId;
                            setTimeout(() => {
                                this.applyElephantMove(elephantTo, 'bot');
                                this.lastSeq++;
                            }, 750);
                        }

                        setTimeout(() => {
                            this.botThinking = false;
                            this.sendAction('playBotTurn', props);
                        }, completed ? 800 : 1550);
                    }, 700);
                },

                // ── Local state + animation ────────────────────────────────
                applyAndAnimateSlide(space, direction, actorId) {
                    const path = E.slidingPositions(space, direction);
                    const v = E.DIR_VECTORS[direction];

                    // Length of the unbroken occupied run from the entry space
                    let run = 0;
                    while (run < 4 && this.board[path[run]]) run++;

                    if (run === 4) {
                        // The far tile is pushed off: animate it out, fade, remove
                        const exiting = this.tiles.find((t) => t.space === path[3]);
                        if (exiting) {
                            exiting.space = -1;
                            exiting.x = this.coords(path[3]).x + v.x * CELL;
                            exiting.y = this.coords(path[3]).y + v.y * CELL;
                            exiting.opacity = 0;
                            exiting.scale = 0.5;
                            setTimeout(() => {
                                this.tiles = this.tiles.filter((t) => t.id !== exiting.id);
                            }, 700);
                        }
                        run = 3;
                    }

                    // Shift the run one space deeper, deepest tile first
                    for (let i = run - 1; i >= 0; i--) {
                        const tile = this.tiles.find((t) => t.space === path[i]);
                        if (tile) {
                            tile.space = path[i + 1];
                            const c = this.coords(path[i + 1]);
                            tile.x = c.x;
                            tile.y = c.y;
                        }
                    }

                    // New tile slides in from off-board
                    const target = this.coords(path[0]);
                    const entering = {
                        id: this.nextId++,
                        x: target.x - v.x * CELL,
                        y: target.y - v.y * CELL,
                        playerId: actorId,
                        space: path[0],
                        opacity: 1,
                    };
                    this.tiles.push(entering);
                    setTimeout(() => {
                        const t = this.tiles.find((t) => t.id === entering.id);
                        if (t) { t.x = target.x; t.y = target.y; }
                    }, 50);

                    // Logical state
                    const result = E.applySlide(this.board, space, direction, actorId);
                    this.board = result.board;
                    this.hands[actorId] = (this.hands[actorId] ?? 0) - 1;
                    if (result.pushedOffOwner) {
                        this.hands[result.pushedOffOwner] = (this.hands[result.pushedOffOwner] ?? 0) + 1;
                    }
                    this.phase = 'move';

                    // Victory check for both actors (mirrors TileSlid::apply)
                    const victors = [];
                    let winning = [];
                    for (const actor of this.actorOrder) {
                        const spaces = E.winningSpaces(this.board, actor, this.shape);
                        if (spaces.length) {
                            victors.push(actor);
                            winning = [...new Set([...winning, ...spaces])];
                        }
                    }
                    if (victors.length) {
                        this.matchStatus = 'complete';
                        this.victorIds = victors;
                        this.winningSpaces = winning;
                    } else if (this.actorOrder.every((a) => (this.hands[a] ?? 0) === 0)) {
                        this.matchStatus = 'complete';
                    }

                    this.persistSnapshot();
                },

                applyElephantMove(space, actorId) {
                    this.elephant = space;
                    this.placeElephant();
                    this.phase = 'tile';
                    const other = this.actorOrder.find((a) => a !== actorId);
                    this.currentActorId = (this.hands[other] ?? 0) > 0 ? other : actorId;
                    this.turnStartedAt = Math.floor(Date.now() / 1000);
                    this.persistSnapshot();
                },

                placeElephant() {
                    const c = this.coords(this.elephant);
                    if (this.$refs.elephant) {
                        this.$refs.elephant.style.transform = `translate(${c.x}px, ${c.y}px)`;
                    }
                },

                buildTilesFromBoard() {
                    const tiles = [];
                    for (let s = 1; s <= 16; s++) {
                        if (this.board[s]) {
                            const c = this.coords(s);
                            tiles.push({
                                id: this.nextId++,
                                x: c.x, y: c.y,
                                playerId: this.board[s],
                                space: s,
                                opacity: 1,
                            });
                        }
                    }
                    this.tiles = tiles;
                },

                // ── Server sync: page-load catch-up + poll bridge ──────────
                snapshotKey() {
                    return 'elephant-snap:' + this.gameId;
                },

                persistSnapshot() {
                    try {
                        localStorage.setItem(this.snapshotKey(), JSON.stringify({
                            lastSeq: this.lastSeq,
                            board: this.board,
                            elephant: this.elephant,
                            phase: this.phase,
                            currentActorId: this.currentActorId,
                            actorOrder: this.actorOrder,
                            hands: this.hands,
                            matchStatus: this.matchStatus,
                            victorIds: this.victorIds,
                            winningSpaces: this.winningSpaces,
                            turnStartedAt: this.turnStartedAt,
                        }));
                    } catch (e) { /* storage full or unavailable — snap instead */ }
                },

                loadSnapshot() {
                    try {
                        const raw = localStorage.getItem(this.snapshotKey());
                        return raw ? JSON.parse(raw) : null;
                    } catch (e) {
                        return null;
                    }
                },

                restoreFrom(snap) {
                    this.board = { ...snap.board };
                    this.elephant = snap.elephant;
                    this.phase = snap.phase;
                    this.currentActorId = snap.currentActorId;
                    this.actorOrder = snap.actorOrder;
                    this.hands = { ...snap.hands };
                    this.matchStatus = snap.matchStatus;
                    this.victorIds = snap.victorIds ?? [];
                    this.winningSpaces = snap.winningSpaces ?? [];
                    this.turnStartedAt = snap.turnStartedAt;
                    this.lastSeq = snap.lastSeq;
                    this.buildTilesFromBoard();
                    this.$nextTick(() => this.placeElephant());
                },

                onServerState({ state, moves }) {
                    if (this.pendingAction || this.animating || this.botThinking) {
                        this.queuedServerState = { state, moves };
                        return;
                    }
                    this.reconcile(state, moves);
                },

                reconcile(state, moves) {
                    if (state.last_seq === this.lastSeq) { this.softAdopt(state); return; }
                    if (state.last_seq < this.lastSeq) {
                        // We optimistically applied a move the server rejected —
                        // roll back by snapping to server truth
                        this.snapTo(state);
                        return;
                    }
                    const missing = (moves ?? []).filter(
                        (m) => m.seq > this.lastSeq && !this.sentMoveIds.includes(m.client_move_id)
                    );
                    const contiguous = missing.length === state.last_seq - this.lastSeq;
                    if (contiguous && missing.length > 0) {
                        this.animateMoveSequence(missing, state);
                    } else {
                        this.snapTo(state);
                    }
                },

                animateMoveSequence(moves, finalState) {
                    this.animating = true;
                    let delay = 0;
                    for (const move of moves) {
                        this.lastSeq = Math.max(this.lastSeq, move.seq);
                        if (move.type === 'tile') {
                            setTimeout(() => this.applyAndAnimateSlide(move.entry_space, move.direction, move.actor_id), delay);
                            delay += 750;
                        } else if (move.type === 'elephant') {
                            setTimeout(() => this.applyElephantMove(move.to_space, move.actor_id), delay);
                            delay += 750;
                        }
                    }
                    setTimeout(() => {
                        this.animating = false;
                        this.softAdopt(finalState);
                    }, delay + 50);
                },

                boardsEqual(serverBoard) {
                    for (let s = 1; s <= 16; s++) {
                        if ((this.board[s] ?? null) !== (serverBoard[s] ?? null)) return false;
                    }
                    return true;
                },

                softAdopt(state) {
                    if (!this.boardsEqual(state.board)) { this.snapTo(state); return; }
                    this.elephant = state.elephant_space;
                    this.phase = state.phase;
                    this.currentActorId = state.current_actor_id;
                    this.hands = { ...state.hands };
                    this.matchStatus = state.match_status;
                    this.victorIds = state.victor_ids ?? [];
                    this.winningSpaces = state.winning_spaces ?? [];
                    this.turnStartedAt = state.turn_started_at;
                    this.lastSeq = state.last_seq;
                    this.placeElephant();
                    this.persistSnapshot();
                    this.maybeRunBot();
                },

                snapTo(state) {
                    this.board = { ...state.board };
                    this.elephant = state.elephant_space;
                    this.phase = state.phase;
                    this.currentActorId = state.current_actor_id;
                    this.actorOrder = state.actor_order;
                    this.hands = { ...state.hands };
                    this.matchStatus = state.match_status;
                    this.victorIds = state.victor_ids ?? [];
                    this.winningSpaces = state.winning_spaces ?? [];
                    this.turnStartedAt = state.turn_started_at;
                    this.lastSeq = state.last_seq;
                    this.buildTilesFromBoard();
                    this.$nextTick(() => this.placeElephant());
                    this.persistSnapshot();
                    this.maybeRunBot();
                },
            };
        };
    </script>
@endonce

@php
    $state = $element['state'];
    // Each shape can show more than one orientation — the L shows both
    // chiralities side by side since either counts on the board
    $shapePatterns = [
        'square' => [[[1, 1], [1, 1]]],
        'line' => [[[1, 1, 1, 1]]],
        'el' => [
            [[1, 0], [1, 0], [1, 1]],
            [[0, 1], [0, 1], [1, 1]],
        ],
        'zig' => [[[1, 1, 0], [0, 1, 1]]],
        'pyramid' => [[[1, 1, 1], [0, 1, 0]]],
    ];
    $shapeVariants = $shapePatterns[$state['victory_shape']] ?? [[[1]]];
@endphp

{{-- ACTION ERROR BANNER --}}
@error('action_error')
    <div
        x-data="{ visible: true }"
        x-init="setTimeout(() => visible = false, 4000)"
        x-show="visible"
        x-transition
        class="fixed top-4 left-1/2 -translate-x-1/2 z-50 rounded-lg shadow-lg bg-amber-500 text-white px-4 py-3 text-sm font-medium"
    >
        {{ $message }}
    </div>
@enderror

{{-- SERVER-STATE SYNC BRIDGE: this node is re-rendered by Livewire on every
     poll/refresh (the uniqid key forces a morph swap), and its x-init pushes
     the authoritative state to the Alpine board below. The board itself lives
     under wire:ignore, so between page loads this event is its only server
     input. --}}
<div
    wire:key="elephant-sync-{{ uniqid() }}"
    x-data
    x-init="window.dispatchEvent(new CustomEvent('elephant-server-state', { detail: {{ Js::from(['state' => $state, 'moves' => $element['moves']]) }} }))"
></div>

<div wire:ignore>
    <script type="application/json" id="elephant-board-config">@json($element)</script>

    <div
        x-data="elephantBoard('elephant-board-config')"
        x-on:elephant-server-state.window="onServerState($event.detail)"
        class="flex flex-col items-center space-y-5 py-4"
    >
        {{-- TARGET SHAPE --}}
        <div class="flex items-center gap-3">
            <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Target shape</span>
            @foreach ($shapeVariants as $variant)
                <div class="flex flex-col gap-px">
                    @foreach ($variant as $row)
                        <div class="flex gap-px">
                            @foreach ($row as $cell)
                                <div class="w-3 h-3 rounded-sm {{ $cell ? 'bg-gray-800' : 'bg-transparent' }}"></div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        {{-- PLAYER CARDS --}}
        <div class="w-full max-w-[300px] space-y-2">
            <template x-for="actorId in actorOrder" :key="actorId">
                <div
                    class="w-full p-3 bg-slate-100 border border-slate-200 rounded-lg transition-all"
                    :class="{
                        'elephant-victory-glow': victorIds.includes(actorId),
                        'animate-pulse': currentActorId === actorId && matchStatus === 'active',
                    }"
                >
                    <div class="flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <div
                                class="w-6 h-6 rounded-lg flex items-center justify-center"
                                :style="`background-color: ${colorFor(actorId)}`"
                            >
                                <p class="font-bold text-white text-sm" x-text="hands[actorId] ?? 0"></p>
                            </div>
                            <p class="text-sm font-medium text-zinc-800">
                                <span x-text="names[actorId] ?? 'Player'"></span>
                                <span x-show="actorId === me" class="text-gray-400">(you)</span>
                            </p>
                        </div>
                        <span
                            x-show="currentActorId === actorId && matchStatus === 'active'"
                            class="text-xs text-gray-500"
                        >their move</span>
                    </div>
                </div>
            </template>
        </div>

        {{-- GAME BOARD with slide arrows on all four edges --}}
        <div class="inline-grid grid-cols-[auto_240px_auto] grid-rows-[auto_240px_auto] gap-1">
            {{-- Top arrows: entries 1-4, direction down --}}
            <div class="col-start-2 row-start-1 h-8">
                <div class="grid grid-cols-4 gap-1" x-show="myTilePhase">
                    <template x-for="i in 4" :key="'top-' + i">
                        <div>
                            <button
                                x-show="isSlideValid(i, 'down')"
                                @click="playerSlide(i, 'down')"
                                class="w-[58px] h-8 animate-pulse flex items-center justify-center text-lg"
                            >↓</button>
                            <div x-show="!isSlideValid(i, 'down')" class="w-[58px] h-8"></div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Left arrows: entries 1,5,9,13, direction right --}}
            <div class="col-start-1 row-start-2 w-8">
                <div class="grid grid-rows-4 gap-1" x-show="myTilePhase">
                    <template x-for="i in 4" :key="'left-' + i">
                        <div>
                            <button
                                x-show="isSlideValid(1 + (i - 1) * 4, 'right')"
                                @click="playerSlide(1 + (i - 1) * 4, 'right')"
                                class="h-[58px] w-8 animate-pulse flex items-center justify-center text-lg"
                            >→</button>
                            <div x-show="!isSlideValid(1 + (i - 1) * 4, 'right')" class="h-[58px] w-8"></div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Main 4x4 grid --}}
            <div class="col-start-2 row-start-2 relative h-[240px] w-[240px] grid grid-cols-4 grid-rows-4 gap-1">
                {{-- Grid spaces + elephant move targets --}}
                <template x-for="i in 16" :key="'space-' + i">
                    <div class="relative">
                        <button
                            x-show="myElephantPhase && validElephantMoveList.includes(i)"
                            @click="playerElephantMove(i)"
                            class="absolute inset-0 bg-slate-800 opacity-20 animate-pulse rounded-lg z-20"
                        ></button>
                        <div
                            x-show="!(myElephantPhase && validElephantMoveList.includes(i))"
                            class="absolute inset-0 bg-gray-100 rounded-lg"
                        ></div>
                    </div>
                </template>

                {{-- Tiles --}}
                <template x-for="tile in tiles" :key="tile.id">
                    <div
                        class="absolute w-[58px] h-[58px] rounded-lg transition-all duration-700 ease-in-out z-10"
                        :class="{ 'elephant-victory-glow': matchStatus === 'complete' && winningSpaces.includes(tile.space) }"
                        :style="`
                            background-color: ${colorFor(tile.playerId)};
                            transform: translate(${tile.x}px, ${tile.y}px) scale(${tile.scale ?? 1});
                            opacity: ${tile.opacity ?? 1};
                        `"
                    ></div>
                </template>

                {{-- Elephant --}}
                <div
                    x-ref="elephant"
                    class="absolute w-[58px] h-[58px] z-30 pointer-events-none flex items-center justify-center text-4xl"
                    :class="{ 'transition-all duration-700 ease-in-out': !initBoard }"
                >🐘</div>
            </div>

            {{-- Right arrows: entries 4,8,12,16, direction left --}}
            <div class="col-start-3 row-start-2 w-8">
                <div class="grid grid-rows-4 gap-1" x-show="myTilePhase">
                    <template x-for="i in 4" :key="'right-' + i">
                        <div>
                            <button
                                x-show="isSlideValid(i * 4, 'left')"
                                @click="playerSlide(i * 4, 'left')"
                                class="h-[58px] w-8 animate-pulse flex items-center justify-center text-lg"
                            >←</button>
                            <div x-show="!isSlideValid(i * 4, 'left')" class="h-[58px] w-8"></div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Bottom arrows: entries 13-16, direction up --}}
            <div class="col-start-2 row-start-3 h-8">
                <div class="grid grid-cols-4 gap-1" x-show="myTilePhase">
                    <template x-for="i in 4" :key="'bottom-' + i">
                        <div>
                            <button
                                x-show="isSlideValid(i + 12, 'up')"
                                @click="playerSlide(i + 12, 'up')"
                                class="w-[58px] h-8 animate-pulse flex items-center justify-center text-lg"
                            >↑</button>
                            <div x-show="!isSlideValid(i + 12, 'up')" class="w-[58px] h-8"></div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- STATUS LINE --}}
        <div class="h-6 text-sm text-slate-700" x-show="matchStatus === 'active'">
            <span x-show="myTilePhase && !opponentOutOfTiles">Your move — slide a tile.</span>
            <span x-show="myTilePhase && opponentOutOfTiles" class="text-amber-600">Opponent is out of tiles — you go again!</span>
            <span x-show="myElephantPhase">Now move the elephant (or leave it).</span>
            <span x-show="botThinking" class="animate-pulse">The Bot is thinking…</span>
            <span x-show="!isMyTurn && !botThinking" class="animate-pulse">
                <span x-text="names[currentActorId] ?? 'Opponent'"></span> is thinking…
            </span>
        </div>

        {{-- TURN TIMER (2-player games only) — expiry auto-ends the game --}}
        <template x-if="!isBotGame && matchStatus === 'active'">
            <div class="w-[240px] space-y-2">
                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div
                        class="h-full transition-all ease-linear"
                        :class="timerUrgent ? 'animate-pulse bg-red-500' : 'bg-gray-600'"
                        :style="`width: ${timerFraction}%`"
                    ></div>
                </div>
                <p
                    x-show="canClaimForfeit"
                    class="text-center text-sm text-amber-600 animate-pulse"
                >
                    Time's up — ending the game…
                </p>
            </div>
        </template>

        {{-- No result card here: the winning tiles glow on the board, and
             the post-game screen (rematch card + final board) lands moments
             later with the full result --}}
    </div>
</div>
