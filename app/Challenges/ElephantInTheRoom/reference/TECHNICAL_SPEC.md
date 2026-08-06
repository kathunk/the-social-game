# Elephant in the Room - iOS Technical Implementation Spec

**Target platforms:** iOS 17+, macOS 14+
**Framework:** SwiftUI (Multiplatform App)
**Language:** Swift

---

## Key Architectural Decisions

1. **Client-first.** All game logic lives on the client. The server is just a state store for multiplayer games.
2. **Offline bot games.** Bot games require zero network. No server, no account, no login. Just play.
3. **Idiomatic iOS.** Use free, well-supported Apple-provided services wherever possible (CloudKit, GameKit, Sign in with Apple, etc.). No attachment to the Laravel backend.
4. **Swipe gestures.** Tile placement uses swipe gestures from edges. Deep investment in haptics + sound + swiping planned for later iterations. V1 ships with basic animations.

---

## Architecture Overview

### Client-First MVVM + Game Engine

```
┌─────────────────────────────────────────┐
│                  Views                   │
│  (SwiftUI: Board, Lobby, Profile, etc.) │
└────────────────┬────────────────────────┘
                 │
┌────────────────▼────────────────────────┐
│              ViewModels                  │
│  (GameViewModel, LobbyViewModel, etc.)  │
└────────────────┬────────────────────────┘
                 │
┌────────────────▼────────────────────────┐
│            Game Engine                   │
│  (Board logic, victory detection,       │
│   slide validation, bot AI)             │
│  Pure value types — no side effects     │
│  RUNS ENTIRELY ON CLIENT                │
└────────────────┬────────────────────────┘
                 │
┌────────────────▼────────────────────────┐
│          Persistence Layer               │
│  Local: SwiftData (bot games)           │
│  Remote: CloudKit/API (multiplayer)     │
└─────────────────────────────────────────┘
```

The **Game Engine** is a standalone, pure-Swift module with zero dependencies on UI, networking, or persistence. It takes a game state + a move, and returns a new game state. This is the single source of truth for game rules.

The **server never validates moves** — the client runs the engine and sends the resulting state to the server for storage and relay to the opponent. This simplifies the server to a dumb pipe.

---

## Game Engine (Pure Logic Layer)

### Board Model

```swift
struct Board {
    // 16 spaces, 1-indexed to match existing logic
    // nil = empty, playerId string = occupied
    var spaces: [Int: String?]  // keys 1...16
    var elephantSpace: Int       // default: 6

    static let empty: Board = Board(
        spaces: (1...16).reduce(into: [:]) { $0[$1] = nil },
        elephantSpace: 6
    )
}
```

**Grid topology** (from the Laravel `adjacentSpaces` mapping):
```
 1 ─ 2 ─ 3 ─ 4
 │   │   │   │
 5 ─ 6 ─ 7 ─ 8
 │   │   │   │
 9 ─10 ─11 ─12
 │   │   │   │
13 ─14 ─15 ─16
```

Row = `(space - 1) / 4`, Column = `(space - 1) % 4`

### Slide Model

```swift
struct Slide: Codable, Equatable {
    let entrySpace: Int        // 1-16 (edge spaces only)
    let direction: Direction   // .up, .down, .left, .right
}

enum Direction: String, Codable {
    case up, down, left, right
}
```

**Sliding positions** define the 4-space path for each valid entry+direction combo. The Laravel version hard-codes these in `slidingPositions()`. There are exactly **16 valid slide configurations** (4 per edge). Port the lookup table directly.

### Slide Execution

The slide logic from `PlayerPlayedTile::applyToGame` is the heart of the game:

1. Read the 4-space path: `[entry, second, third, fourth]`
2. If all 4 occupied: tile at position 4 is **pushed off** → returns to owner's hand
3. Cascade: shift occupants forward along the path
4. Place the new tile at the entry space
5. Decrement current player's hand count

**Key invariant:** The elephant blocks slides. Port `slideIsBlockedByElephant()` exactly — it checks if the elephant sits at any position in the path that would require tile movement.

### Victory Detection

```swift
struct VictoryResult {
    let hasWon: Bool
    let winningSpaces: [Int]
}

enum VictoryShape: String, Codable, CaseIterable {
    case square, line, el, zig, pyramid
}
```

Port the victory set constants directly from `BoardLogic.php`:
- Square: 9 configurations
- Line: 8 configurations
- El: 48 configurations (8 orientations)
- Zig: 24 configurations (4 orientations)
- Pyramid: 24 configurations (4 orientations)

Victory is checked by testing if any configuration has all 4 spaces occupied by the same player. The JS version (`game-logic.js`) and PHP version are identical — port either one.

**Important:** Victory is checked after tile placement, not after elephant movement. Both players can win simultaneously.

### Turn Flow State Machine

```swift
enum GamePhase: String, Codable {
    case placeTile    // "tile"
    case moveElephant // "move"
}

enum GameStatus: String, Codable {
    case created, active, complete, canceled
}
```

State transitions:
```
created ──(both players join)──→ active
active:
  placeTile ──(tile placed)──→ moveElephant
  moveElephant ──(elephant moved)──→ placeTile (switch player*)
active ──(victory/draw/forfeit)──→ complete
created ──(timeout/leave)──→ canceled
```

*Turn passes to the other player UNLESS the other player has 0 tiles in hand. In that case, the current player keeps going until a tile gets pushed back to the opponent.

### Valid Moves Computation

At any point, the engine should compute:
- `validSlides(board:elephantSpace:)` → `[Slide]` (all 16 possible minus elephant-blocked ones)
- `validElephantMoves(elephantSpace:)` → `[Int]` (adjacent spaces + current space)

These are used by both the UI (to show/hide interaction targets) and by the bot AI.

---

## Bot AI

### Scoring

The bot evaluates every valid slide using `boardScore()`:

```swift
func boardScore(_ board: Board, botId: String, opponentId: String) -> Int {
    var score = 0
    score += adjacentTileCount(for: botId, in: board)
    score -= adjacentTileCount(for: opponentId, in: board)
    if hasCheck(botId, in: board)      { score += 100 }
    if hasCheck(opponentId, in: board) { score -= 200 }
    if isVictorious(opponentId, board) { score -= 1000 }
    if isVictorious(botId, board)      { score += 1_000_000_000 }
    if botRunsOutOfTiles(board)        { score -= 500 }
    return score
}
```

- All valid slides are scored and ranked
- Elephant movement is currently **random** (TODO: add scoring)
- "Check" detection uses shape-specific 3-tile subpatterns (extensive lists in `BotLogic.php`)

### Difficulty Levels

All difficulties use the same scoring function. The only difference is **how the bot picks from the ranked list**:

| Difficulty | Selection Strategy |
|------------|-------------------|
| **Hard** | Always picks the highest-scoring move |
| **Medium** | Randomly picks from the top 2 scoring moves |
| **Easy** | Randomly picks from the top 3 scoring moves |

This is clean — one scoring function, one ranked list, three selection strategies. Ship with **Hard only** for v1.

### Implementation Notes

- Bot runs entirely on the client — no server needed
- Run bot computation on a background thread
- Add a short artificial delay (500ms-1s) so it feels like the bot is "thinking" rather than instant
- The scoring function is deterministic for a given board state, but moves are shuffled before scoring to break ties randomly

---

## Data Models

### Local Models (Client-Side)

```swift
// Core game state — this is what gets persisted
struct GameState: Codable {
    let id: UUID
    var status: GameStatus
    var board: [Int: String?]     // 16 spaces
    var elephantSpace: Int
    var phase: GamePhase
    var currentPlayerId: String
    var player1: PlayerState
    var player2: PlayerState
    var victorIds: [String]
    var winningSpaces: [Int]
    var isRanked: Bool
    var moves: [MoveRecord]
}

struct PlayerState: Codable {
    let id: String
    let name: String
    var hand: Int                 // starts at 8
    let victoryShape: VictoryShape
    let isBot: Bool
    var wantsRematch: Bool
    var rating: Int
}

struct MoveRecord: Codable {
    let playerId: String
    let type: MoveType            // .tile or .elephant
    let boardBefore: [Int: String?]
    let boardAfter: [Int: String?]
    let elephantBefore: Int
    let elephantAfter: Int
    let slide: Slide?             // only for tile moves
}

enum MoveType: String, Codable {
    case tile, elephant
}
```

### Multiplayer-Specific Models

```swift
// Only needed when syncing with server
struct RemoteGame: Codable {
    let id: String
    let hostName: String
    let hostRating: Int
    let isRanked: Bool
    let isFriendsOnly: Bool
    let victoryShape: VictoryShape
    let createdAt: Date
}

struct UserProfile: Codable {
    let id: String
    let name: String
    var rating: Int
}

struct Friendship: Codable {
    let id: String
    let friendId: String
    let friendName: String
    let status: FriendshipStatus  // .pending, .accepted
}
```

### IDs

- **Local bot games**: Use `UUID` — no server involved
- **Multiplayer games**: Use whatever the server provides (UUID or server-generated)

---

## Persistence

### Two Tiers

| Tier | Storage | Use Case |
|------|---------|----------|
| **Local** | SwiftData | Bot games (in-progress + completed), user preferences, cached multiplayer state |
| **Remote** | Server (TBD) | Multiplayer game state, user accounts, ratings, friendships |

### Local Persistence (SwiftData)

Bot games are fully local. SwiftData stores:
- In-progress bot games (resume after app kill)
- Completed bot game history (optional — for stats/replay)
- User preferences (mute, default settings)
- Cached user profile (name, rating) for offline display

### Remote Persistence (Multiplayer Only)

The server stores:
- Game state snapshots (board, players, moves)
- User accounts and ratings
- Friendships
- Lobby state (open games waiting for opponents)

The client pushes state to the server after each move. The server relays it to the opponent. The server does NOT validate moves — it trusts the client.

---

## Networking (Multiplayer Only)

### Server Options (TBD — pick what's most idiomatic)

| Option | Pros | Cons |
|--------|------|------|
| **CloudKit** | Free, Apple-native, zero server to manage, handles auth via Apple ID | Limited real-time (CKSubscription), non-trivial for turn-based |
| **GameKit (Game Center)** | Built for turn-based multiplayer, handles matchmaking/auth/notifications | Opinionated API, limited customization |
| **Firebase** | Free tier, real-time database, easy auth, cross-platform | Google dependency |
| **Custom server** | Full control | Must build and host |

**Recommendation:** Evaluate GameKit first — it's purpose-built for exactly this kind of turn-based game. If it's too limiting, fall back to CloudKit or Firebase.

### What the Server Needs to Support

Regardless of provider, the multiplayer backend needs:

1. **Authentication** — identify users
2. **Lobby** — list open games, create game, join game
3. **State sync** — push game state after each move, pull opponent's moves
4. **Notifications** — "your turn" push notifications
5. **Ratings** — store and update Elo ratings
6. **Friends** — friend requests, friend list

### Real-Time Communication

For the "your turn" notification during an active game:

- **GameKit**: Built-in turn notifications
- **CloudKit**: CKSubscription + push notifications
- **Firebase**: Realtime Database listeners
- **Custom**: WebSocket or server-sent events

The web version uses WebSockets for instant updates + 4-second polling fallback. The iOS version should feel at least as responsive.

### Data Flow (Multiplayer Game)

```
Player A plays tile
  → Engine computes new state locally
  → UI animates immediately (optimistic)
  → Client pushes new state to server
  → Server stores state, notifies Player B
  → Player B's client receives state
  → Player B's UI animates opponent's move
```

No server-side validation. The client is authoritative.

---

## UI Architecture

### Screen Flow

```
Launch
  ├── Bot Game (no auth needed)
  │     └── Game Screen (offline)
  │
  └── Multiplayer (auth required)
        ├── Home
        │     ├── Active Game → rejoin
        │     ├── Join Game → list of open games
        │     ├── New Game → create game with options
        │     └── Rules → tutorial/rules viewer
        ├── Friends → friends list management
        └── Profile → name, rating, settings

Game Screen
  ├── Player info cards (top)
  ├── Game board (center)
  │     ├── 4x4 grid
  │     ├── Tile layer (animated)
  │     ├── Elephant layer (animated)
  │     ├── Swipe zones (edges, when tile phase)
  │     └── Elephant move targets (overlay, when elephant phase)
  ├── Turn timer (multiplayer only, below board)
  ├── Status text ("Opponent is thinking...")
  └── Post-game overlay (Rematch button)
```

### Game Board View (SwiftUI)

```swift
struct GameBoardView: View {
    @ObservedObject var viewModel: GameViewModel

    var body: some View {
        ZStack {
            // Background grid (4x4 rounded rects)
            GridBackground()

            // Tiles (positioned absolutely, animated)
            ForEach(viewModel.tiles) { tile in
                TileView(tile: tile, isWinning: viewModel.winningSpaces.contains(tile.space))
                    .position(viewModel.pointForSpace(tile.space))
                    .animation(.easeInOut(duration: 0.7), value: tile.space)
            }

            // Elephant
            ElephantView()
                .position(viewModel.pointForSpace(viewModel.elephantSpace))
                .animation(.easeInOut(duration: 0.7), value: viewModel.elephantSpace)

            // Elephant move overlay (tap targets)
            if viewModel.isElephantPhase {
                ElephantMoveOverlay(validMoves: viewModel.validElephantMoves) { space in
                    viewModel.moveElephant(to: space)
                }
            }
        }
        .frame(width: 240, height: 240)
        // Swipe gesture recognizers on edges for tile placement
        .gesture(slideGesture)
    }
}
```

### Swipe Gesture for Tile Placement

V1 approach — detect swipes from edges:

```swift
// Detect swipe direction + position along the edge
// Map to a Slide (entrySpace + direction)
// Only allow if slide is in validSlides
```

The swipe should feel like you're "pushing" a tile onto the board from the edge. The entry point determines which row/column, the swipe direction determines the slide direction.

### Animation Strategy (V1 — Basic)

```swift
// Tile movement: SwiftUI position animation
withAnimation(.easeInOut(duration: 0.7)) {
    tile.space = newSpace  // position updates via computed property
}

// Tile entrance: start off-screen, animate to board position
// Tile pushed off: animate to exit position, fade + scale, then remove

// Elephant: same position-based animation
withAnimation(.easeInOut(duration: 0.7)) {
    elephantSpace = newSpace
}
```

**Animation sequencing** (opponent's turn):
1. Play tile slide animation (immediate)
2. Wait 700ms
3. Play elephant movement animation

Use `Task` + `try await Task.sleep(for: .milliseconds(700))`.

**Important:** Block user input during animations via an `animating` flag on the ViewModel.

---

## Audio

### Sound Assets

Port the existing audio files:
- `slide_1.mp3` through `slide_5.mp3` — random selection on tile placement
- `elephant_1.mp3`, `elephant_2.mp3` — random on elephant movement (only when space changes)
- `victory.mp3` — player wins
- `defeat.mp3` — opponent wins

### Implementation

```swift
class AudioManager {
    static let shared = AudioManager()

    var isMuted: Bool {
        get { UserDefaults.standard.bool(forKey: "gameAudioMuted") }
        set { UserDefaults.standard.set(newValue, forKey: "gameAudioMuted") }
    }

    func playSlide() { play("slide_\(Int.random(in: 1...5))") }
    func playElephant() { play("elephant_\(Int.random(in: 1...2))") }
    func playVictory() { play("victory") }
    func playDefeat() { play("defeat") }
}
```

Use `AVAudioPlayer` for short sound effects.

---

## Testing Strategy

### Unit Tests (Game Engine) — Highest Priority

Port these test files directly from the Laravel codebase:
- `TileMovementTest` → test slide mechanics, cascading, push-off, hand management
- `ElephantTest` → test movement, blocking rules
- `VictoryTest` → test all victory shapes, simultaneous wins, draws
- `GamePhasesTest` → test turn flow, phase transitions, empty hand skipping
- `RatingTest` → test Elo calculation
- `BotTest` → test bot move selection and difficulty selection strategies

The test helpers in `TestCase.php` (like `bootMultiplayerGame`, `dumpBoard`) should be ported as test fixtures.

### Integration Tests

- Multiplayer state sync (mock network layer)
- Animation timing
- Gesture recognition

---

## Rating System

Port the Elo implementation from `User::calculateNewRating`:

```swift
static func calculateNewRating(
    playerRating: Int,
    opponentRating: Int,
    result: GameResult  // .win, .loss, .draw
) -> Int {
    let kFactor = 32.0
    let expected = 1.0 / (1.0 + pow(10.0, Double(opponentRating - playerRating) / 400.0))
    let actual: Double = switch result {
        case .win: 1.0
        case .loss: 0.0
        case .draw: 0.5
    }
    let newRating = Double(playerRating) + kFactor * (actual - expected)
    return max(100, min(3000, Int(newRating.rounded())))
}
```

Rating only applies to ranked multiplayer games. Bot games never affect rating.

---

## Project Structure (Suggested)

```
ElephantInTheRoom/
├── App/
│   ├── ElephantApp.swift
│   └── AppState.swift
├── Engine/                        // Pure game logic (no dependencies)
│   ├── Board.swift
│   ├── Slide.swift
│   ├── VictoryDetection.swift
│   ├── VictoryShapes.swift        // The big constant tables
│   ├── ElephantLogic.swift
│   ├── BotAI.swift
│   └── Rating.swift
├── Models/
│   ├── GameState.swift
│   ├── PlayerState.swift
│   ├── MoveRecord.swift
│   └── UserProfile.swift
├── Persistence/
│   ├── LocalGameStore.swift       // SwiftData for bot games
│   └── RemoteGameStore.swift      // Multiplayer sync (TBD provider)
├── ViewModels/
│   ├── GameViewModel.swift
│   ├── LobbyViewModel.swift
│   ├── HomeViewModel.swift
│   └── FriendsViewModel.swift
├── Views/
│   ├── Game/
│   │   ├── GameBoardView.swift
│   │   ├── TileView.swift
│   │   ├── ElephantView.swift
│   │   ├── SlideGesture.swift
│   │   ├── PlayerInfoCard.swift
│   │   └── TurnTimerView.swift
│   ├── Home/
│   │   ├── HomeView.swift
│   │   ├── GameListView.swift
│   │   └── NewGameView.swift
│   ├── Lobby/
│   │   └── PreGameLobbyView.swift
│   ├── Auth/
│   │   └── SignInView.swift
│   ├── Friends/
│   │   └── FriendsListView.swift
│   ├── Rules/
│   │   └── RulesView.swift
│   └── Components/
│       ├── VictoryShapeIcon.swift
│       └── ElephantIcon.swift
├── Audio/
│   └── AudioManager.swift
├── Resources/
│   ├── Audio/                     // .mp3 files
│   └── Assets.xcassets            // Colors, images
└── Tests/
    ├── EngineTests/
    │   ├── BoardTests.swift
    │   ├── SlideTests.swift
    │   ├── VictoryTests.swift
    │   ├── ElephantTests.swift
    │   ├── BotTests.swift
    │   └── RatingTests.swift
    └── ViewModelTests/
        └── GameViewModelTests.swift
```

---

## Colors

From tailwind config:

| Name | Light | Dark | Usage |
|------|-------|------|-------|
| Player 1 | `#FF6857` (orange) | `#A81100` (dark-orange) | Player tiles, hand count badge |
| Player 2 | `#007393` (teal/light-teal) | `#00607A` (dark-teal) | Opponent tiles, hand count badge |

Define these in `Assets.xcassets` with light/dark variants.

---

## V1 Scope

### Ship

- Bot games (offline, single difficulty = hard)
- Full game engine (all victory shapes, slide mechanics, elephant)
- Basic SwiftUI animations (700ms ease-in-out)
- Sound effects
- Mute toggle
- Dark mode

### Defer

- Multiplayer (requires server decision + implementation)
- Swipe gesture polish (haptics, edge-swipe feel)
- Multiple bot difficulties (medium/easy)
- Rating system (needs multiplayer)
- Friends system (needs multiplayer)
- Push notifications
- Game replay viewer
- Onboarding tutorial

---

## Open Technical Questions

1. **Multiplayer backend**: GameKit (built-in turn-based), CloudKit (Apple-native), Firebase (easy real-time), or custom server? This is the biggest remaining decision but doesn't block v1 (bot-only).

2. **SwiftUI vs SpriteKit for the board**: SwiftUI is simpler but may struggle with complex coordinate-based animations. SpriteKit is purpose-built for 2D games. Worth prototyping both. V1 can start with SwiftUI and migrate the board view if needed.

3. **Haptic feedback design**: When to trigger haptics — tile slide, elephant move, victory, blocked move? What intensity? This is a later polish item but worth thinking about early.

4. **macOS-specific input**: The app targets macOS 14. Should the Mac version support keyboard input (arrow keys)? Click-on-edge instead of swipe? Window resizing?

5. **Game state sync strategy**: When the app returns from background mid-multiplayer-game, how should we reconcile state? Full fetch from server?

6. **Connection loss**: The web version has a 35-second forfeit timer. If the iOS app loses connectivity, the player could auto-forfeit. Reconnection grace period?

7. **Onboarding**: Interactive tutorial? Animated demos (like the web GIFs)? Or just let people figure it out?

8. **Accessibility**: VoiceOver support level? The game is spatial — announcing board state could be verbose.

9. **Local two-player (pass-and-play)**: Worth building for v1? The engine supports it trivially — it's just UI.

10. **Simultaneous victories**: Both players can win at the same time (draw). Is this intended? Or should we add a tiebreaker?
