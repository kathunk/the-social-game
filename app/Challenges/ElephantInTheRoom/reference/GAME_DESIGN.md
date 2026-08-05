# Elephant in the Room - Game Design Document

## Overview

**Elephant in the Room** is a two-player abstract strategy board game played on a 4x4 grid. Players take turns sliding tiles onto the board and moving a shared elephant piece that blocks tile slides. The first player to arrange four of their tiles into their assigned **victory shape** wins.

---

## Core Concepts

### The Board

A 4x4 grid with 16 spaces, numbered 1-16 in reading order:

```
 1   2   3   4
 5   6   7   8
 9  10  11  12
13  14  15  16
```

The board starts empty except for the elephant, which begins on space **6**.

### Players

- Each game has exactly **2 players**
- Each player starts with **8 tiles** in their hand
- Both players are assigned the **same victory shape** for a given game
- Players are distinguished by color:
  - Player 1 (host): **Orange** (`#FF6857` light / `#A81100` dark)
  - Player 2 (opponent): **Teal** (`#007393` light / `#00607A` dark)

### The Elephant

A shared piece that sits on the board. It **blocks tile slides** and can be **moved** by the current player after they place a tile. The elephant is a neutral piece - it belongs to neither player.

---

## Turn Structure

Each turn consists of two phases:

### Phase 1: Place a Tile ("tile" phase)

The current player **slides a tile** onto the board from one of the 16 edge positions (4 edges x 4 columns/rows). A slide has:
- A **starting space** (one of the edge spaces)
- A **direction** (up, down, left, right — always pointing inward)

**Valid slide entry points:**
| Direction | Entry spaces |
|-----------|-------------|
| Down (from top) | 1, 2, 3, 4 |
| Right (from left) | 1, 5, 9, 13 |
| Left (from right) | 4, 8, 12, 16 |
| Up (from bottom) | 13, 14, 15, 16 |

**Sliding mechanics:**
- The new tile enters at the edge space and pushes along the row/column
- If the entry space is occupied, the occupant shifts to the next space, cascading up to 3 tiles deep
- If a tile is pushed off the far end of the board (4th position), it returns to its owner's hand
- A slide is **blocked by the elephant** if the elephant sits in the path where tiles need to move

**Elephant blocking rules (detailed):**
The elephant blocks a slide if:
1. The elephant is on the entry space itself, OR
2. There's a tile at the entry space AND the elephant is at position 2, OR
3. There are tiles at positions 1-2 AND the elephant is at position 3, OR
4. There are tiles at positions 1-3 AND the elephant is at position 4

In essence: the elephant acts as a wall. Tiles cannot slide through or into the elephant.

### Phase 2: Move the Elephant ("move" phase)

After placing a tile, the player **must move the elephant**. The elephant can move to:
- Any **adjacent space** (orthogonal, not diagonal)
- OR **stay in place** (the current space is always a valid move)

The elephant cannot move diagonally. Adjacency follows the 4x4 grid topology.

### Turn Passing

After moving the elephant, the turn passes to the opponent — **unless the opponent has no tiles in hand**. If the opponent's hand is empty, the current player continues taking turns. If a tile gets pushed off and returns to the empty-handed opponent, they regain a tile and the turn passes normally again.

---

## Victory Conditions

### Victory Shapes

There are **5 possible victory shapes**, each representing a specific arrangement of 4 tiles on the board. Both players in a game share the same victory shape. The shape can appear in **any valid rotation/reflection** on the 4x4 grid.

#### 1. Square
```
X X
X X
```
A 2x2 block. 9 possible positions on the board.

#### 2. Line
```
X X X X    or    X
                 X
                 X
                 X
```
Four tiles in a straight line (horizontal or vertical). 8 possible positions.

#### 3. El (L-shape)
```
X X X    X X    X        X    (and all rotations)
    X    X      X    X X X
             X X
```
An L-shaped tetromino in all 8 orientations. 48 possible positions.

#### 4. Zig (S/Z-shape)
```
X X      X      X X        X
  X X    X X      X X    X X
                         X
```
An S/Z tetromino in all 4 orientations. 24 possible positions.

#### 5. Pyramid (T-shape)
```
X X X      X     X      X
  X      X X     X    X X
               X X X    X
```
A T-shaped tetromino in all 4 orientations. 24 possible positions.

### Victory Detection

Victory is checked **after every tile placement** (not after elephant movement). This means:
- A player can win on their own turn by placing a tile that completes their shape
- A player can also win when their **opponent's** slide pushes their tiles into the winning shape
- **Both players can win simultaneously** (a "cat's game" / draw) — both are recorded as victors

### Game End Conditions

The game ends when:
1. **A player (or both) forms their victory shape** after a tile is placed
2. **Both players run out of tiles** — the game ends with no victor (draw)
3. **A player forfeits** by running out their turn timer

---

## Game Modes & Options

### Game Creation Options

| Option | Description |
|--------|-------------|
| **Play first** | Toggle whether the creator goes first |
| **Ranked** | Uses Elo rating system (disabled for bot games) |
| **Friends only** | Only friends can join (disabled for bot games) |
| **Practice against bot** | Single-player mode vs AI |

### Victory Shape Assignment

- **Multiplayer games**: Random from all 5 shapes (square, line, el, zig, pyramid)
- **Bot games**: Random from 3 shapes only (square, line, el) — zig and pyramid excluded

Both players always receive the **same** victory shape.

### Turn Timer

- Each player has **35 seconds** per turn
- The timer is a progress bar that fills from left to right
- Last 10 seconds: bar turns red and pulses
- If timer expires: automatic forfeit — opponent wins
- Timer resets on each turn transition
- When the opponent is thinking (not your turn), no timer is shown — instead "Opponent is thinking..." appears

### Forfeit Timer System

- A scheduled command runs **every 30 seconds** checking for expired turn timers
- Games where the creator doesn't get a second player within **5 minutes** are auto-canceled (checked every minute)

---

## Matchmaking & Game Lifecycle

### Lifecycle States

```
created → active → complete
  ↓
canceled
```

1. **Created**: Host creates a game, waits for opponent
2. **Active**: Both players joined, game in progress
3. **Complete**: Victory, draw, or forfeit
4. **Canceled**: No opponent joined within 5 minutes, or host leaves lobby

### Joining a Game

- The home page shows available games (status = "created") with:
  - Creator's name
  - Creator's rating
  - Whether the game is ranked
- Friends-only games are only visible to friends
- When player 2 joins from the lobby, the host can start the game
- When joining from the home page directly, the game auto-starts

### Pre-Game Lobby

- Shows a shareable link for inviting friends
- Displays joined players with their ratings
- Host can start once both players are present
- Host can leave (cancels the game)
- Polls every 10 seconds to check if the game was canceled

### Rematch System

- After a game ends, either player can request a rematch
- If both players want a rematch, a new game is created automatically
- Rematch settings inherit from the previous game (ranked, friends-only)
- The **loser goes first** in the rematch
- For bot games, the bot always wants a rematch
- Both players poll (4 second interval) to detect when the rematch game is created

---

## Social Features

### Friends System

- Users can add friends by email
- Friendship states: `not_friends`, `request_outgoing`, `request_incoming`, `friends`
- Friend requests can be accepted or rejected
- During a game, you can send/accept friend requests with your opponent
- Friends-only games filter visibility on the home page

### Rating System

- **Elo rating** with K-factor of 32
- Starting rating: **1000**
- Rating range: 100 - 3000 (clamped)
- Only applies to **ranked** games (bot games are never ranked)
- Rating updates occur immediately when the game ends
- Draw gives both players 0.5 score

**Elo formula:**
```
expected = 1 / (1 + 10^((opponent_rating - player_rating) / 400))
new_rating = rating + K * (actual_score - expected_score)
```

Where actual_score is 1.0 (win), 0.0 (loss), or 0.5 (draw).

---

## Audio

The game features sound effects (toggleable mute, persisted in localStorage):

| Sound | Files | Trigger |
|-------|-------|---------|
| Tile slide | `slide_1.mp3` through `slide_5.mp3` (random) | Any tile placement |
| Elephant move | `elephant_1.mp3` through `elephant_2.mp3` (random) | Elephant moves to a different space |
| Victory | `victory.mp3` | Current player wins |
| Defeat | `defeat.mp3` | Opponent wins |

---

## Visual Design

### Board Layout

- Grid: 240x240px with 4x4 cells, 1px gap
- Each cell: 58x58px with rounded corners
- Empty spaces: light gray background
- Slide buttons: directional arrows (↑↓←→) around the board edges, pulsing when available
- Elephant moves: target spaces pulse with semi-transparent overlay when selectable

### Tile Appearance

- Rounded rectangles (58x58px)
- Player 1: Orange
- Player 2: Teal
- Winning tiles: "victory-wave-glow" CSS animation
- Tiles pushed off board: fade out + scale down, then removed after 700ms

### Player Info Cards

- Show player name, hand count (colored badge), rating (star icon)
- Victory shape SVG displayed next to each player
- Active player's card pulses
- Victor's card gets victory glow animation

### Animations

- All tile/elephant movements use CSS transitions: `duration-700ms ease-in-out`
- New tile slides in from off-screen edge to its target position
- Pushed tiles cascade simultaneously
- Tiles pushed off-board animate to the exit edge, then fade

### Dark Mode

Full dark mode support with tailored color variants.

---

## Bot AI

### Strategy

The bot evaluates all valid slides using a **scoring function**, then ranks them. Difficulty is controlled by how the bot picks from its ranked list:

| Difficulty | Selection Strategy |
|------------|-------------------|
| **Hard** | Always picks the highest-scoring move |
| **Medium** | Randomly picks from the top 2 scoring moves |
| **Easy** | Randomly picks from the top 3 scoring moves |

This is clean and simple — the scoring logic is identical across difficulties, only the selection changes.

**Tile placement scoring:**
| Factor | Score |
|--------|-------|
| Bot has winning position | +1,000,000,000 |
| Bot has "check" (3 tiles toward victory) | +100 |
| Adjacent tile bonus (per adjacency) | +1 |
| Opponent has winning position | -1,000 |
| Opponent has "check" | -200 |
| Bot runs out of tiles (all 8 placed) | -500 |
| Opponent adjacent tiles (per adjacency) | -1 |

**Elephant movement:** Currently random among valid moves (TODO: scoring).

**"Check" detection** varies by victory shape — the bot looks for specific 3-tile subpatterns that are one tile away from forming the full victory shape.

### Bot Behavior

- The bot always uses `bot@bot.bot` user account
- Bot games are never ranked
- Bot automatically takes its turn after the human player
- Bot games only use square, line, or el victory shapes
- Bot always wants a rematch
- Turn-taking is recursive: if the bot skips an opponent with empty hand, it keeps going

---

## Decided for iOS Migration

1. **Board interaction model**: Swipe gestures from board edges. Deep investment in haptics, sound, and swiping — but v1 ships with basic animations. Polish in later iterations.

2. **Architecture**: Client-first. All game logic lives on the client. Bot games are fully offline — no server needed. Multiplayer games store state on a server, but the client is authoritative for game logic. Server is just a state store.

3. **Server**: Whatever is most idiomatic and best-supported from the iOS ecosystem. No attachment to the Laravel backend. Use free, well-supported Apple-provided services where possible.

4. **Bot difficulty**: Ship with one difficulty (hard) initially. The architecture supports easy/medium/hard via selection strategy (see Bot AI section). This is handled entirely client-side.

---

## Open Questions for iOS Migration

1. **Elephant movement UX**: Currently valid elephant spaces pulse. Tap-to-select? Or swipe gesture too?

2. **Matchmaking**: Simple lobby list, auto-match by rating, or GameCenter integration?

3. **Push notifications**: When it's your turn, should we send push notifications? What about friend requests?

4. **Game history/replay**: The `Move` model stores full board state before/after every move. Should we build a replay viewer?

5. **Spectating**: Not in the current version. Worth adding?

6. **Victory shape preference**: Should players be able to choose or veto victory shapes?

7. **Accessibility**: What level of VoiceOver support should the iOS app target?

8. **Monetization**: Any plans for premium features, cosmetic tiles, etc.?

9. **iPad/Mac considerations**: The multiplatform target includes macOS 14. Should the Mac version support mouse hover states? Keyboard shortcuts?

10. **Onboarding**: The web version shows rules via GIF tutorials. Interactive tutorial for mobile?

11. **Social integration**: Beyond friends, any plans for chat, emoji reactions during games, or sharing game results?

12. **Account system**: Sign in with Apple? Game Center? Both?

13. **Simultaneous victories**: Both players can win at the same time (draw). Is this intended as a feature, or would you prefer a tiebreaker rule?

14. **Local two-player (pass-and-play)**: Worth building?

15. **Skip turn display**: When a player has no tiles and their turn is skipped, there's no explicit UI feedback in the web version. Should the iOS app show something?
