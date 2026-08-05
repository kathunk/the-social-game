# Elephant in the Room — Implementation Scoping Doc

**Status:** Phase 1 scoping doc — finalized. All open inputs resolved; original code received in `reference/`. No code written yet.
**Verdict:** This fits the app. Every piece maps onto an existing, shipped pattern (MorningRoutine is the primary template, TierList for match-ends-itself, Farm for bespoke board UI). The genuinely new things are small, fully mode-scoped, and listed explicitly in [What's new / risk register](#whats-new--risk-register).

---

## Decisions already made (with John, 2026-08-05)

| Decision | Choice |
|---|---|
| Game setup | No custom lobbies/matchmaking. Existing PreGameLobby + link/QR join. Two seeded game modes: 2-player, and 1-player vs bot. |
| Bot representation | **Virtual bot** — a sentinel `'bot'` actor id inside `challenge_data`. Not a User, not a Player row. |
| Bot brain | **Fully client-side JS.** Server validates move *legality* via Verbs events but never second-guesses the bot's *choice*. Note: the original app's bot was server-side (`takeBotTurnIfNecessary()`), so there is no JS bot code to port — the brain is a fresh JS write against derived tables (see learnings section). |
| Scoring | Win = 1 point, loss = 0 (via `addToScoreHistory`). Draw/simultaneous win = both in `victor_ids`, both get 1. |
| Confirmed defaults | Creator goes first and is Orange; 20-minute match ceiling. |
| Realtime (2p) | **Existing architecture only** (revised by John during build): `GameUpdatedForReverb` fired at turn boundaries (after the elephant move, or on match completion) — the dashboard answers with its usual full refresh, exactly like MorningRoutine. The board blade persists a snapshot + last-seen sequence number in localStorage and, on every page load, animates whatever the server's move log holds beyond the snapshot; your own moves are already in your snapshot so they never re-animate. `wire:poll` (3s) + a sync-bridge node is the fallback when websockets are down. No custom broadcast events. |
| Turn timer | **Lazy claim-forfeit**: server stamps `turn_started_at`; waiting player's client calls `claimForfeit` after 35s; server validates timestamps. No cron/schedule changes. |
| V1 extras | **None.** No sound, no results recap challenge, hard-difficulty bot only. |
| Cut entirely | Elo/ratings, friends system, matchmaking home list, rematch system, lobby auto-cancel, "play first" toggle, ranked/friends-only options, dark mode (app is light-locked), swipe gestures (tap arrows instead), audio. |

---

## File map (everything that gets created or touched)

```
app/Challenges/ElephantInTheRoom/
    ElephantMatch.php                       # the one challenge = the whole match
    Support/
        BoardLogic.php                      # pure static engine: slide paths, cascade,
                                            #   elephant blocking, victory sets, adjacency
                                            #   (plain-class precedent: MorningRoutine/Rewards/)
app/Events/ElephantInTheRoom/
    TileSlid.php                            # Verbs event
    ElephantMoved.php                       # Verbs event
    MatchForfeited.php                      # Verbs event
    ElephantMoveBroadcast.php               # plain Laravel event, ShouldBroadcastNow (NOT Verbs)
app/Support/FormBuilderTraits/ElephantInTheRoom/
    ElephantFormElements.php                # ->elephantBoard(...)
resources/views/components/game-components/custom-form-elements/
    elephant-board.blade.php                # board UI + @once JS engine + Alpine + Echo listener
database/seeders/ElephantInTheRoom/
    ElephantInTheRoomSeeder.php             # GameModeAdded + GameTemplateAdded × 2 modes
tests/Feature/Challenges/
    ElephantInTheRoomTest.php               # Livewire feature tests (required)

TOUCHED (trip-wire, needs sign-off — see risk register):
app/Support/FormBuilder.php                 # one `use ElephantFormElements;` line
```

Nothing else. No changes to GameDashboard, HandlesClassActions, BaseChallengeClass, registries, form.blade.php, routes, channels, schedules, or generic events. Challenge/event classes auto-register via directory scan.

---

## Game modes & seeder

Two `GameModeAdded` + `GameTemplateAdded` pairs (mirror `MorningRoutineSeeder`):

| | Elephant in the Room | Elephant in the Room (vs Bot) |
|---|---|---|
| `type` | `individual` | `individual` |
| `min_players` / `max_players` | 2 / 2 | 1 / 1 |
| `players_can_join_late` | false | false |
| `team_names` | `[]` | `[]` |
| `modifiers` | `[]` | `[]` |
| `challenges` | `[[ [elephant_match], duration: 20 ]]` | same |
| `scoreboard_type` | `individual` | `individual` |
| `is_public` | **false initially** (dark launch, super-admin only), flip when ready | same |

Existing min/max enforcement (lobby Start gating + `is_joinable`) gives us exact player counts for free. The 20-minute challenge duration is the match's hard ceiling — the existing `app:progress-games` scheduler ends it if nobody wins (scored as a draw in `onChallengeEnded`). Home page card: falls back to the generic mode card automatically; custom card optional later via `GameModeCardRegistry`.

**Open default (confirm):** game creator plays first, is Orange (`#FF6857`); second player/bot is Teal (`#007393`).

---

## The match challenge

`ElephantMatch extends BaseChallengeClass` — `TYPE = 'individual'`, `HIDE_SCOREBOARD = true`, `key() = 'elephant_match'`.

### `challenge_data` schema (initialized in `dataArrayForState()`)

```php
[
    'board'            => [1 => null, ..., 16 => null], // actor id or null
    'elephant_space'   => 6,
    'phase'            => 'tile',                       // 'tile' | 'move'
    'current_actor_id' => '<creator player id>',        // player id or 'bot'
    'actor_order'      => ['<p1 id>', '<p2 id or bot>'],
    'hands'            => ['<p1>' => 8, '<p2|bot>' => 8],
    'victory_shape'    => 'square',                     // rolled here; 5 shapes for 2p, 3 for bot games
    'is_bot_game'      => false,
    'match_status'     => 'active',                     // 'active' | 'complete'
    'victor_ids'       => [],                           // both filled on simultaneous win (draw)
    'winning_spaces'   => [],
    'turn_started_at'  => <unix ts>,
    'moves'            => [],                           // append-only move log, see below
    'last_seq'         => 0,
]
```

Bot game detection: `$this->challenge->game->players->count() === 1` at start. Randomness (shape roll) happens in `dataArrayForState()`, whose return value becomes the `ChallengeStarted` event payload — so it's captured in the event stream and replay-safe (existing pattern).

**Move log entry** — the backbone of animation catch-up, dedupe, and idempotency:

```php
['seq' => 7, 'actor_id' => ..., 'type' => 'tile'|'elephant'|'forfeit',
 'client_move_id' => '<uuid from client>',
 'slide' => ['entry' => 3, 'direction' => 'down'] | 'to_space' => 11,
 'pushed_off_owner' => null|'<actor id>',   // tile returned to hand
 'at' => <unix ts>]
```

### Action methods (all `(Player $player, array $params)`, all wrapped in a `withChallengeLock()` copied from MorningRoutine)

| Action | Fires | Notes |
|---|---|---|
| `slideTile` | `TileSlid` | params: `entry_space`, `direction`, `client_move_id` |
| `moveElephant` | `ElephantMoved` | params: `to_space`, `client_move_id` |
| `playBotTurn` | `TileSlid` + `ElephantMoved` (actor `'bot'`) | Bot's full turn in one request: `bot_entry_space`, `bot_direction`, `bot_to_space`, client move ids. Only valid in bot games when `current_actor_id === 'bot'`. |
| `claimForfeit` | `MatchForfeited` | Only valid for the *waiting* player once `now() >= turn_started_at + 35s + 3s grace`. |

Each action: `Event::fire(...)` → `Verbs::commit()` → if 2p game, `event(new ElephantMoveBroadcast(...))` → if `match_status === 'complete'`, end-of-match sequence (below).

### Match end (TierList precedent — challenge ends itself)

```php
$this->challenge->fresh()->end();                    // triggers onChallengeEnded scoring
$this->challenge->next()?->start() ?? $this->challenge->game->end();
Verbs::commit();
event(new GameUpdatedForReverb($player->game->fresh()));   // the ONE per-match firing; dashboard redirect is desired here
```

`onChallengeEnded(GameState $game_state)` reads `challenge_data` off `$this->challenge_state` (state-side hydration — documented MorningRoutine gotcha) and writes `addToScoreHistory()` on each real player's `PlayerState`. Draw (simultaneous victory or double-empty-hands or time expiry) scores both. The `'bot'` actor gets nothing — it isn't a player. **Point values TBD (see open inputs).**

---

## Verbs events

All use `HasGame` + `HasChallenge` traits and a plain `public string $actor_id` (NOT `HasActivePlayer` — the bot isn't a Player, and turn identity lives in `challenge_data`). All `validate()` reads `ChallengeState` (not models) so multi-event batches see each other. All timestamps/randomness passed as payload (replay determinism). `handle()` persists via `Challenge::find($this->challenge_id)?->update(['challenge_data' => ...])` (standard pattern).

**`TileSlid`** — `validate()`: challenge active, `match_status` active, phase `tile`, `actor_id === current_actor_id`, actor's hand > 0, slide in the 16-config table, not elephant-blocked, **`client_move_id` not already in the move log** (idempotency — a retried/duplicated request is a hard no-op failure, not a double move). `apply(ChallengeState)`: run `BoardLogic::applySlide()` (cascade up to 3 deep, push-off returns tile to its owner's hand), decrement hand, run victory detection **for both actors** (opponent's slide can win you the game; simultaneous win = both in `victor_ids`), append move log, `phase = 'move'`. If victory or both hands empty with empty... (double-out-of-tiles draw handled here too) → `match_status = 'complete'`.

**`ElephantMoved`** — `validate()`: phase `move`, actor is current, `to_space` adjacent-or-same, `client_move_id` fresh. `apply()`: move elephant, `phase = 'tile'`, advance `current_actor_id` **unless the other actor's hand is empty** (then current actor goes again — the empty-hand skip rule), stamp `turn_started_at` from payload, append move log.

**`MatchForfeited`** — `validate()`: match active, claimant is the *non-current* actor, `forfeited_at >= turn_started_at + 38`. `apply()`: `match_status = 'complete'`, `victor_ids = [claimant]`, log entry.

**`ElephantMoveBroadcast`** (plain Laravel event, not Verbs) — `ShouldBroadcastNow`, `PrivateChannel('games.'.$game_id)`, custom `broadcastAs('elephant.move')`. Payload: `seq`, `actor_id`, `type`, `client_move_id`, the move itself, resulting `phase` / `current_actor_id` / `turn_started_at`, and victory info if any. Deliberately *not* the whole board — receiving client applies the move to its local model; the poll fallback provides full-state reconciliation.

`app/Challenges/ElephantInTheRoom/Support/BoardLogic.php` holds the hardcoded tables (16 slide paths, adjacency map, victory sets: square 9, line 8, el 48, zig 24, pyramid 24) and pure functions used by both events and tests. Ported 1:1 from the original `BoardLogic.php`.

---

## Frontend: board blade + optimistic UI protocol

One custom form element (`type: 'elephant_board'` → `elephant-board.blade.php`), emitted by `ElephantFormElements::elephantBoard(...)` carrying: full `challenge_data`, this player's actor id, opponent display info, `is_bot_game`, colors. Plus `->poll(5000)` as reconciliation fallback. `HIDE_SCOREBOARD` gives us the whole viewport (app is ≤640px mobile-first; the 240px board fits fine).

### Structure inside the blade

- `@once <script>` block (the sanctioned way to ship challenge JS — no Vite/bundle changes):
  - **JS engine** ported from original `game-logic.js`: slide paths, cascade simulation, elephant blocking, victory sets, check detection, bot scoring + hard-difficulty pick. Single source file kept in visual parity with `BoardLogic.php`.
  - `Alpine.store('elephant')` for anything that must survive a Livewire re-render.
- `x-data` component owning: local board model (positions keyed by tile identity for CSS-transition animation), `phase`, `currentActorId`, `lastAnimatedSeq`, `sentMoveIds` (Set), `animating` input lock, 35s turn-timer ticker off `turn_started_at`.
- Interaction: pulsing directional arrow buttons on the 16 edge entries during your `tile` phase (only legal ones — engine filters elephant-blocked); pulsing overlay targets on adjacent spaces during your `move` phase. Tap, not swipe.
- All animation = CSS transitions (`700ms ease-in-out`) on absolutely-positioned tiles, matching the original. Pushed-off tiles animate to the exit edge, fade, then return to the owner's hand badge.

### The optimistic protocol (the heart of this build)

**Your own move:**
1. Generate `client_move_id` (uuid), add to `sentMoveIds`, lock input.
2. Apply the move to the local model via the JS engine and animate **immediately**.
3. `$wire.callClassAction('slideTile', 'challenge', key, ...)` with the move + `client_move_id`.
4. Server confirms (event committed, broadcast fired). When your own move echoes back — via broadcast or poll — it's in `sentMoveIds` / `seq <= lastAnimatedSeq`, so it is **reconciled silently, never re-animated**. This is the exact dedupe requirement from the original app, done with a per-move id instead of heuristics.
5. **Failure** (validation reject, lock timeout, network): Livewire's existing error path runs `softRefresh()` → blade re-renders with authoritative `challenge_data` → Alpine snaps the local model back to server truth and shows the `action_error` banner. Rollback = resync, no bespoke undo logic.

**Opponent's move (2p):** Echo listener `window.Echo.private('games.' + gameId).listen('.elephant.move', ...)`. If `client_move_id ∉ sentMoveIds` and `seq > lastAnimatedSeq` → apply to local model with animation (tile slide 700ms, then elephant move 700ms, sequenced), bump `lastAnimatedSeq`. Channel auth already exists (`games.{id}` gated on game membership).

**Reconciliation (poll or reconnect):** every 5s re-render delivers authoritative `challenge_data`. Alpine diffs the server move log against `lastAnimatedSeq`: unseen moves get animated in order (this is also how a reloaded/returning tab catches up); if local and server board diverge otherwise, snap to server. The existing `app.js` already forces a global Livewire `$refresh` on websocket reconnect and tab-visible — we inherit that for free.

**Bot games:** no Echo needed at all. Human's elephant move completes → client's JS brain computes the bot's full turn instantly, animates it after a ~700ms "thinking" delay, and calls `playBotTurn` with the chosen moves. Server validates legality (turn, phase, blocking, adjacency) and records it. If the server rejects (should only happen on client-engine bug), the standard resync path recovers and it surfaces loudly in logs.

**Turn timer:** progress bar fills over 35s from `turn_started_at` (client-side ticker — same technique as MorningRoutine's countdowns), red pulse in last 10s. On expiry, the *waiting* player's client shows/auto-triggers "Claim win" → `claimForfeit`. Your own expired timer shows "time's up" state but the server only acts when the opponent claims.

---

## Learnings from the original code (`reference/OldGameView.php` + `reference/old-game-view.blade.php`)

Read on 2026-08-05. What we port, what we change, and two deltas from what the specs implied.

### Port directly (proven mechanics)

- **`wire:ignore` board root + one big Alpine `x-data` owning the DOM.** Livewire never re-renders the board; Alpine is the sole DOM owner. This is also the existing tier-list-guess pattern in this repo. Adopt as-is.
- **Tile rendering model:** a `tiles` array of `{id, x, y, playerId, space}`; each tile is an absolutely-positioned div with `transition-all duration-700 ease-in-out` and `transform: translate(x, y)`; `spaceToCoords()` maps space → pixels (58px cells + 1px gaps, 240px board). New tiles spawn off-board at the entry edge, then get their target coords ~50ms later so the transition fires. Pushed-off tiles get exit coords + `opacity: 0, scale: 0.5`, removed after 700ms, and the owner's hand count increments.
- **`shiftTilesFrom()` recursive cascade** — the client-side slide engine, including row-boundary guards for horizontal slides and depth-3 push-off. This *is* the JS slide logic; no separate `game-logic.js` port needed for sliding.
- **Optimistic own-move in one click handler:** `@click="playTile(...); $wire.playTile(...)"` — local animation and server call fired together. Same for elephant. Keep, adapted to `callClassAction`.
- **Opponent turn sequencing:** animate tile slide, then elephant 700ms later. The original's elephant broadcast carries *both* the tile-move id and elephant-move id so a client that missed the tile broadcast still replays it — deduped via a `known_move_ids` list seeded from the server's move log at init. Our `seq` + `client_move_id` move-log design is the same idea generalized; keep ours.
- **Arrow buttons / elephant targets:** edge arrows shown only for entries present in `valid_slides`, pulsing; elephant targets as semi-transparent pulsing overlays on `valid_elephant_moves`. Port markup nearly verbatim (minus dark-mode classes — this app is light-locked).
- **"Opponent is thinking..." + forfeit progress bar:** 35s bar computed client-side from a timestamp, red pulse in last 10s, 100ms ticker. Port the bar; repoint it at `turn_started_at`.
- **Victory glow:** `victory-wave-glow` CSS class on winning tiles + winner's player card. Port the CSS into the blade.

### Change deliberately (two deltas from the original)

1. **Bot was server-side in the original** (`$opponent->takeBotTurnIfNecessary()` after the human's elephant move and on every 4s poll; `BotLogic.php` held scoring + hardcoded "check" subpattern lists). Per our decision the brain moves to client JS. We do **not** have the original `BotLogic.php` / `game-logic.js` tables, and we don't need them: victory sets (square 9, line 8, el 48, zig 24, pyramid 24) and check detection are **derived programmatically** — generate all placements of each tetromino on the 4x4 grid once; "check" = any victory config with exactly 3 own tiles and the 4th space empty. One generator, mirrored PHP/JS, no 48-row tables to mistype. (If John digs up the originals, they become a cross-check fixture, nothing more.)
2. **Forfeit was self-inflicted in the original:** the timed-out player's *own* client fired `GameForfeited`, backed by a 30s server sweep command. We keep the decided **claim-forfeit** instead (waiting player claims after 35s + grace) — more robust when the offender closes their tab, and needs no scheduled command. The specs' 30s "opponent has no tiles" special-case timer is dropped; one uniform 35s turn timer.

### Also noted

- The original used `@entangle` heavily to sync Alpine ↔ Livewire props. In our architecture the challenge blade gets state as plain element data (re-derived per render) plus broadcast payloads — no entangle needed; the move-log diff is the sync mechanism.
- The original's per-move-type broadcast events (`PlayerPlayedTileBroadcast`, `PlayerMovedElephantBroadcast`) validate our single `ElephantMoveBroadcast` with a `type` field.
- The original component's echo handlers did the self-move filter server-side (`if ($move->player_id === $this->player->id) return;`). Ours does it client-side via `sentMoveIds`/`seq` — necessary because we can't add listeners to GameDashboard (trip-wire).

---

## What's new / risk register

Ordered by novelty. Everything here is scoped to this mode's files.

1. ~~Blade-level Echo listener + mode-scoped broadcast event~~ — **dropped at John's direction.** Realtime rides entirely on `GameUpdatedForReverb` + the dashboard's existing refresh; the blade handles cross-reload animation catch-up via a localStorage snapshot keyed by game id.
2. **Rules engine duplicated in JS and PHP.** Accepted cost of the client-side bot + optimistic animation. Mitigation: the JS slide/animation engine is ported from the original blade (proven code, in `reference/`); victory/check tables are generated programmatically from one algorithm mirrored in both languages rather than transcribed; PHP side pinned by exhaustive Pest fixtures. There is no JS test runner in this repo, so JS parity is held by the shared generator approach + the Livewire tests exercising server acceptance of client-produced moves.
3. **Turn-based play.** No precedent in the app (all existing challenges are simultaneous). It reduces to `current_actor_id` + event `validate()` assertions + the existing cache-lock pattern — in-pattern, just new.
4. **Client-authored bot moves.** Server validates legality, not choice; only exploitable against yourself in a solo game. Explicitly decided.
5. **Trip-wire touch:** one `use ElephantFormElements;` line in `app/Support/FormBuilder.php`. Precedent: Farm, TierList, MorningRoutine all did exactly this; still requires John's sign-off per CLAUDE.md. **Target: zero other trip-wire touches.** (MorningRoutine's PR also touched `HandlesClassActions` by 4 lines — if implementation hits a wall that seems to need that, stop and ask, per the rules.)

Explicitly *not* risks: no schema/migrations, no new routes/channels, no scheduler changes, no npm dependencies, no Vite config changes.

---

## Testing plan (`tests/Feature/Challenges/ElephantInTheRoomTest.php`)

Mirror `MorningRoutineTest` structure: `Verbs::commitImmediately()`, `mockGameTemplate(...)`, `createGame()`/`createPlayer()`, `game->start()`, a `callAction()` helper driving `Livewire::test(GameDashboard::class)->call('callClassAction', ...)`, and a `mutateState()` helper for hard-to-reach board positions.

Coverage checklist:
- **Slides:** entry on empty board; cascade 1/2/3 deep; push-off returns tile to correct owner's hand; all 16 slide configs valid on empty board.
- **Elephant blocking:** all 4 blocking rules from the design doc, plus arrow-availability implications.
- **Elephant moves:** adjacency, stay-in-place, diagonal rejected, wrong-phase rejected.
- **Turn law:** out-of-turn rejected; wrong-phase rejected; empty-hand skip (current player keeps going; turn restored when a push-off refills the hand).
- **Victory:** at least one full test per shape + spot-checks across orientations (table-driven over `BoardLogic` victory sets); win via your own slide; win via opponent's slide pushing your tiles; simultaneous win → both in `victor_ids`, draw scoring; double-empty-hands draw.
- **Idempotency:** duplicate `client_move_id` rejected.
- **Forfeit:** claim too early rejected; claim by current player rejected; valid claim ends match with claimant as victor.
- **Bot turn:** `playBotTurn` rejected in 2p games, rejected when not bot's turn, rejected for illegal bot move; legal bot turn applies both phases.
- **Lifecycle:** victory ends the challenge and the game (single-challenge template); time-expiry end scores a draw; `onChallengeEnded` writes score history for real players only.
- **Concurrency:** two racing actions → second fails cleanly with `action_error` (lock + validate).

---

## Delivery plan (small, reviewable PRs)

1. **PR 1 — Server core.** `BoardLogic`, the three Verbs events, `ElephantMatch` (actions, lifecycle, scoring), seeder (modes dark: `is_public: false`), full Pest/Livewire test suite. Board renders as a minimal static grid (no animation) so tests and manual 2-tab play work end to end.
2. **PR 2 — The board.** Full blade UI: JS engine port, optimistic protocol, `ElephantMoveBroadcast` + Echo listener, animations, turn-timer bar + claim-forfeit UI. This PR includes the one-line `FormBuilder.php` touch.
3. **PR 3 — The bot.** JS brain (scoring + hard pick), `playBotTurn` wiring, vs-Bot mode enabled. Flip `is_public` when happy.

---

## Inputs from John — all resolved 2026-08-05

1. ~~Original code~~ — received: `reference/OldGameView.php` + `reference/old-game-view.blade.php`. (`game-logic.js` / `BotLogic.php` not needed — tables derived programmatically; welcome later as cross-check fixtures if found.)
2. ~~Point values~~ — win 1, loss 0; draw = both victors, 1 each. Same for bot games.
3. ~~First player~~ — confirmed: creator goes first, is Orange.
4. ~~Match ceiling~~ — confirmed: 20 minutes.
5. **Still open: sign-off on the one `FormBuilder.php` line** (risk register item 5) — assumed yes given precedent, will flag in the PR.

**Built and shipped as a single PR at John's request: [#150](https://github.com/kathunk/the-social-game/pull/150).**
