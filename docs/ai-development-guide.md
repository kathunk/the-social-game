# Development Guide

Orientation for contributors. For architectural rules read `/CLAUDE.md`; for the system mental model read `docs/architectural-patterns.md`; for a how-to read `docs/ai-quick-start.md`. This file covers everything else.

## Stack

- **Laravel 12** (PHP 8.3+) — application framework.
- **Livewire 3** — server-rendered reactive UI components. The only frontend framework in use.
- **Alpine.js** — small-scale client-side interactivity. Used inside Livewire components.
- **Flux UI** — the design-system component library (`<flux:input>`, `<flux:select>`, etc.).
- **Tailwind CSS** — styling.
- **Verbs** (`hirethunk/verbs`) — event sourcing. All state changes go through it.
- **Laravel Reverb** — WebSocket server for real-time updates (`GameUpdatedForReverb` event, etc.).
- **Laravel Horizon** — queue dashboard.
- **Laravel Cashier (Stripe)** — payments / membership.
- **Pest** — test framework.

## Directory map

```
app/
├── Challenges/         # per-mode challenge classes (see architectural-patterns.md)
├── Modifiers/          # per-mode modifier classes
├── Events/             # Verbs events (top-level = lifecycle, subfolders = mode-specific)
├── States/             # Verbs state objects
├── Livewire/           # Livewire components, including the generic dashboard
├── Models/             # Eloquent models
├── Support/            # FormBuilder + per-mode form-builder traits
├── Http/Controllers/   # thin controllers (most logic is in Livewire)
├── Jobs/               # queued jobs
├── Observers/          # Eloquent observers
├── Providers/          # service providers
└── Rules/              # custom validation rules
config/                 # Laravel config files
database/migrations/    # schema
database/seeders/       # per-game-mode seeders
docs/                   # this directory
mcp/                    # MCP server (currently unused; do not rely on it for behavioral rules)
resources/views/        # Blade templates
resources/views/livewire/                       # Livewire component templates
resources/views/components/game-components/     # generic game UI components
resources/views/components/game-components/custom-form-elements/  # per-mode custom UI
routes/web.php          # routes
tests/Feature/          # tests (Pest)
tests/Helpers/          # test helpers
tests/TestCase.php      # base TestCase with mockGameTemplate, createGame, createPlayer
```

## Commands

```bash
# Web + assets
php artisan serve              # web server on :8000
npm run dev                    # frontend build (vite, hot reload)

# Background services (run each in its own terminal during dev)
php artisan queue:work         # process queued jobs
php artisan reverb:start       # websocket server (required for real-time updates)

# Logs / monitoring
php artisan pail               # tail logs in real time
php artisan horizon            # queue dashboard at /horizon

# Tests
php artisan test               # run all feature tests
php artisan test --filter=BloodOath  # filter to one file or test name

# Game data utilities
php artisan db:reset-data            # wipe + reseed local game data
php artisan create:bots              # create bot user accounts
php artisan fill:game-with-bots      # join bots to a game
php artisan progress:games           # advance current challenge if its time is up
php artisan fake:laracon-activity    # generate sample activity

# Verbs
php artisan verbs:replay             # rebuild state from event log
php artisan verbs:replay-selective   # replay specific events

# Admin
php artisan promote:super-admin      # promote a user
```

## Environment

Local dev is set up via [Laravel Herd](https://herd.laravel.com/) on macOS. The `.env` is created from `.env.example`. Key env vars:

- `DB_*` — MySQL connection.
- `STRIPE_*` — payments (`STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`).
- `BROADCAST_DRIVER=reverb` — websocket transport.
- `REVERB_*` — Reverb server credentials.
- `QUEUE_CONNECTION` — usually `redis` locally.
- `MAIL_*` — mail driver (typically `log` locally).
- `TELEGRAM_*` — optional Telegram integration.

`.env.testing` is used when running tests. SQLite in memory; no external services required.

## Models at a glance

- `User` — accounts.
- `Game` — a single game instance.
- `Player` — a user's participation in a specific game (one user can be many players across many games).
- `Team` — a team within a game.
- `Challenge` — one round within a game; persistence of a `ChallengeState`.
- `Modifier` — a modifier active in a game.
- `GameMode` / `GameTemplate` — the template a game is created from.
- `GameApplication` — a user's request to join a game.
- `Membership` — subscription state.
- `ModifierConfiguration` — pre-game configuration for a modifier with `REQUIRES_PRE_GAME_CONFIGURATION = true`.

## Key routes

`routes/web.php` is the authoritative source. Notable Livewire routes:

- `/` → `Home`
- `/games/create` → `CreateGame`
- `/games/{game}` → `GameDashboard`
- `/games/{game}/lobby` → `PreGameLobby`
- `/games/{game}/secrets` → `SecretsPage` (modifiers with dedicated pages)
- `/games/{game}/player/{player}` → `PlayerPage`
- `/games/{game}/team/{team}` → `TeamPage`
- `/game-modes` → `GameModesListPage`

## Debugging

- **State drift**: if Eloquent and Verbs state disagree, you almost certainly bypassed an event somewhere. `php artisan verbs:replay` rebuilds from the event log — if the rebuild matches what you expect, the bug is in direct DB writes; if not, it's in an event's `apply()` or `handle()`.
- **Stale view**: Livewire properties are stale until `softRefresh()` runs or the component re-mounts. `Verbs::commit()` + `softRefresh()` is the standard sequence — `HandlesClassActions::callClassAction()` already does this after every action.
- **Failed assertion in tests**: check that you called `->fresh()` on the model before asserting, and that `Verbs::commitImmediately()` is set.
- **WebSocket updates not arriving**: confirm Reverb is running (`php artisan reverb:start`) and `BROADCAST_DRIVER=reverb`.

## On adding new tech

Don't, unless you've talked to the repo owner. The stack above is deliberate. In particular: no React/Vue, no other event-sourcing libraries, no alternative test frameworks, no replacement for FormBuilder. Adding a JS library at all is a yellow flag — most things belong in Livewire/Alpine.
