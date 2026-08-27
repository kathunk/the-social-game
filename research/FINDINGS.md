# Elephant in the Room — Bot Research Findings

*Aug 2026. Produced by the `research/` simulation lab on the `elephant-bot-research`
branch of the-social-game. Engine validated against
`app/Challenges/ElephantInTheRoom/Support/BoardLogic.php` (victory lists
cross-checked against programmatic tetromino generation; blocking/cascade/turn
rules unit-tested).*

## What was tested

A ladder of five bots, round-robin over thousands of non-visual Python games:

| Bot | Description |
|-----|-------------|
| B0-random | Random slide + random elephant |
| B1-current | Faithful replica of the shipped bot (greedy slide scoring, random elephant) |
| B2-guard | B1's slide choice + defensive elephant (minimize opponent's immediate winning slides) |
| B3-tactician | Joint (slide, elephant) 1-ply search; threat counting, fork bonus, live-config progress, repetition avoidance |
| B4-lookahead | B3 + explicit opponent best-reply search (2-ply) |

## Headline results

Wins among decisive games, alternating first mover:

| Matchup | square | line | el | zig | pyramid |
|---------|--------|------|----|----|---------|
| B1 vs B0 (300/shape) | 299–0 | 297–3 | 297–2 | 297–3 | 298–2 |
| B2 vs B1 (200/shape) | 119–72 | 115–79 | 109–87 | 109–84 | 97–80 |
| B3 vs B1 (200/shape) | 195–4 | 182–17 | 119–79 | 180–17 | 168–30 |
| B4 vs B3 (100/shape) | 67–21 | 66–20 | 57–24 | 82–9 | 77–23 |
| B4 vs B1 (100/shape) | 96–2 | 96–3 | 70–29 | — | — |

Ranked by what the upgrade was worth:

1. **Lookahead + joint move choice (B3) is the big jump.** Choosing the slide
   and elephant move *together*, scoring the position you hand the opponent,
   and counting threats (not just detecting one) took the current bot from
   champion to a 2-in-100 underdog.
2. **A defensive elephant alone (B2) is worth ~60/40** over the current bot —
   the cheapest meaningful upgrade, one function.
3. **2-ply search (B4) is worth another ~75/25** over 1-ply.

## Scrutiny of the current bot's logic

The greedy score-every-slide structure is sound, and +100 own-check / -200
opponent-check correctly prices defense above offense. What holds it back:

- **Random elephant is the biggest leak.** The elephant is the game's only
  blocking mechanism; under strong play it performs most threat defense.
- **Checks are boolean.** One check and a double check (fork) score the same,
  but a single check is nearly always neutralizable and a fork nearly always
  wins. The bot neither builds forks nor fears them.
- **No lookahead**, so "-200 for opponent check" can't distinguish "harmless,
  elephant covers it" from "game over."
- **Adjacency is shape-agnostic** — a 2x2 clump scores well even when the goal
  is a line.
- Two latent bugs in `BotLogic.php` (already fixed in the iOS rewrite): the
  line-check list has 4-element entries whose 4th space is never tested, and
  `hypotheticalBoardAfterSlide` hardcodes `player_2_id` as the mover.

## Game-design findings (worth a look beyond the bot)

1. **There is no repetition rule, and infinite games are real.** Two competent
   defenders lock into perpetual push/counter-push cycles (one slides a tile
   in, the other pushes it back off, forever). The turn timer hides this in
   human play, but a losing player can legally stall forever. Chess's answer
   is threefold repetition = draw; worth considering.
2. **El (and pyramid) look like first-player forced wins.** At B4 strength,
   self-play on el and pyramid is **100% first-player wins in 9–11 plies**
   (60/60 each), and the el line survives 20/20 against a defender searching
   *all* of its candidate replies. Not a formal proof, but strong evidence.
   El is currently in the bot-game shape pool; a human who learns the line
   below beats any bot every time they move first.
3. **Square and line are the balanced, deep shapes.** Under strong self-play,
   first-mover advantage nearly vanishes (46–62%), games run long (45–51
   plies), and real draws appear. These are the shapes where skill expresses.
4. **Zig is fortress-prone**: 82% first-mover advantage at B3 level *and* the
   highest draw/stall rate (30%). Weakest shape of the five.
5. **The "cat's game" double win is a unicorn** — 0 occurrences in 600
   instrumented strong games. Fine to keep as flavor.
6. First-mover advantage at current-bot level is enormous everywhere
   (67–83% in B1 self-play). "Loser goes first in the rematch" is doing more
   balancing work than it probably gets credit for.

## The el kill (concrete forced-looking line)

Build a protected middle-row triple with a spur. Example final position —
P1 tiles on 6, 7, 8, 11, elephant parked on 7:

```
.   2   .   .
2   1   1E  1
.   .   1   .
.   2   2   .
```

From here P1 completes an L at **2, 4, 10, or 12** — four completion squares.
The elephant can cover one, a placed tile another; nothing covers four. The
elephant on 7 doubles as armor: no slide can push through it to break up the
row. Getting there: run tiles up/in through the center columns, pivot the
column triple into a row triple, walk the elephant into your own structure.

## How a human should play (the generalizable rules)

1. **The elephant is a defensive piece first.** Under strong play, 88–100% of
   single threats get neutralized, and the elephant does the majority of that
   work. Every turn, before anything else, ask: *where does my opponent
   complete their shape next turn?* Park the elephant on that square, or in
   the slide lane they'd need to push through.
2. **One threat never wins; two threats win.** In instrumented strong games,
   zero wins came from a threat the opponent was allowed to see and answer —
   wins came from forks (two simultaneous completion squares) or threats that
   were physically impossible to cover. Don't cash your check; build the
   second one.
3. **Park the elephant inside your own structure.** It armors your tiles:
   nothing can slide through the elephant, so a triple with the elephant in it
   can't be pushed apart. Offense and defense with one piece.
4. **Win by pushing, not placing.** On square and line, 95–99% of strong wins
   are cascade wins — the winning slide *pushes existing tiles* into the shape
   rather than dropping a tile into an open square. Open completion squares
   are visible and blockable; a push assembles the shape in one motion from a
   direction the elephant often can't cover. Read the board as "what does each
   lane look like after a push," not "which squares are empty."
5. **Fight for the center.** The four center squares appear in the most
   victory configurations, and the win-location heatmaps for every shape
   concentrate there. Center tiles are also one push away from more configs.
6. **Tempo is tiles in hand.** Running out of tiles forfeits your turns until
   a push returns one. Winners on el/pyramid finish with 3+ tiles in hand;
   even in long square/line grinds, the player who runs dry first gives away
   free turns at the worst moment.
7. **Move first and play sharp.** At human-realistic skill, first-mover
   advantage is 65–85%. If you're second, spend your early turns on defense
   and elephant position — the counter-attack comes after the first wave
   breaks.
8. **Know that stalling is legal.** With no repetition rule, a disciplined
   defender can refuse to lose in fortress positions by pushing the same tile
   off forever. If you're better, vary the position; if you're worse, the
   fortress is your friend.

## Recommendations for the production bot

- **Port B3 (tactician) as the new hard bot.** It's one evaluation function
  over ~80 joint (slide, elephant) moves — no deep search, PHP-friendly, and
  it beats the current hard bot ~95/5. Core ingredients, in order of value:
  count opponent winning slides after your full move (huge penalty each),
  count your own standing threats (+fork bonus), live-config progress
  (partial shapes not contaminated by opponent/elephant), small
  repetition penalty, keep-a-tile-in-hand penalty.
- **B4 (2-ply) as an optional "soul-crushing" mode** — measurably stronger
  (~3:1 over B3); costs ~100–300ms/move in Python, similar in PHP. Could gate
  behind a "hard+" toggle.
- **Difficulty ladder for free:** easy = current bot (random elephant),
  medium = B2 (current + defensive elephant), hard = B3, nightmare = B4. This
  replaces the top-2/top-3 selection hack with genuinely different play
  styles — weaker bots lose for human-like reasons (they forget the elephant).
- **Reconsider el in the bot shape pool** (and pyramid if it's ever added):
  first player appears to win by force at high skill. Square and line are the
  shapes with real depth.
- **Consider a threefold-repetition draw rule** to close the infinite-stall
  loophole.

## Repetition rule experiments (Aug 2026 follow-up)

Proposal tested: repetition should be a LOSS for the repeater (not a chess-style
draw). Two definitions were simulated (`experiment_repetition.py`):

- **move3** — playing the same slide three times in a row loses.
- **pos3** — recreating the same position (tiles + elephant + player to move)
  for the third time loses.

Results:

1. **Both rules kill stalling stone dead.** Against a motivated staller,
   games without a rule stall out 7–60% of the time; with either rule, 150/150
   games end decisively on every shape tested. Repetition-as-loss also has the
   right competitive shape: the fortress-holder is the one forced to give
   ground, so the attacker gets rewarded and games resolve.
2. **But move3 outlaws normal play.** In strong, healthy, decisive self-play
   games (no stalling involved), a player slides the same lane three times in
   a row in **51–92% of games** depending on shape — loading a column/row by
   repeated pushes is a core legitimate pattern. Unaware shipped-tier bots
   lose by move3 ambush in 59% of games.
3. **pos3 only fires on actual repetition.** By definition it cannot trigger
   unless the whole position has already occurred twice — i.e. genuine
   stalling. Ambush rate on unaware players is roughly half of move3's, and
   a UI warning ("this move would repeat the position a third time — you'd
   lose") reduces it to zero while still forcing the staller to give ground.

**Round 2 (move4, after position-based was rejected as un-visualizable):**
"same slide 4 times in a row = loss" was tested the same way, plus a harder
adversary: a *smart* staller that, when blocked, makes the most
stall-preserving legal deviation (rotate lanes: A,A,A,B,A,A,A,B...) instead
of a random one.

- move4 vs naive stallers: 150/150 decisive, same as move3.
- Ambush rate on unaware players: roughly half of move3's (23–49%).
- Collateral in healthy strong games: a 4-in-a-row same slide occurs
  naturally in 23% (zig) to 89% (pyramid) of decisive games — so as a
  *hidden* loss rule it ambushes; as a *visible* constraint it's a real but
  fair strategic limit ("rotate lanes").
- **Two smart stallers can still fortress forever under move4**: 75–77% of
  square/line games hit the 300-ply cap. The move-based rule is evadable in
  principle.
- **One smart staller vs a player trying to win**: 75–100% of games resolve
  (13–25% residual stalls on square/line at bot strength). Compared to no
  rule at all (60–90% stalls), move4 removes most of the problem.

**Recommendation:** implement move4 as a **visible UI constraint** — the 4th
consecutive identical slide is simply illegal (arrow greys out, with a pip
counter), equivalently "you lose if you somehow submit it" with a clear
warning. Zero memory burden (the UI counts for you), zero ambush, kills all
naive stalling. Residual exotic stalling (two players rotating lanes in
mutual agreement to draw) remains theoretically possible but requires both
players to prefer not winning, and the 35s turn timer bounds it in practice.
If real-world stalling abuse ever shows up, position-based repetition
(airtight by pigeonhole: every position can occur at most twice, so games
must end) is the escalation path.

## Open questions / next steps

- **Solve the game.** State = (board, elephant, turn) with hands derivable;
  a retrograde analysis or proof-number search (likely in a compiled language,
  or overnight Python) could turn "el looks won for P1" into a theorem, and
  give exact game values for all five shapes.
- Tune B3's weights by self-play (they're first-guess round numbers).
- Verify the B3 eval ports cleanly to PHP within the 35s turn budget
  (it will — it's milliseconds).

## Reproducing

```
cd research
python3 test_engine.py                 # 10 rule-validation tests
python3 tournament.py --games 200      # full ladder round-robin
python3 tournament.py --mirror B3-tactician --games 400
python3 analyze.py --games 120         # behavioral telemetry + heatmaps
```
