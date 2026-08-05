# Architectural Patterns

This is the canonical architectural reference for The Social Game. It describes the system as it actually exists in code today. **If anything here contradicts the code, the code is right and this doc is wrong** — update it.

Companion docs:
- `/CLAUDE.md` — the non-negotiable rules (start here)
- `docs/ai-quick-start.md` — a cookbook for adding a challenge or modifier
- `docs/testing-guidelines.md` — testing patterns
- `docs/module-mapping.md` — current per-game-mode file layout

## Mental model

A **Game** is composed of two primitives:

- **Challenges** (`app/Challenges/`) — one round of play. Has UI, an action handler, and lifecycle hooks.
- **Modifiers** (`app/Modifiers/`) — rules that change how the game works for its entire duration. Has UI (sometimes), an action handler, and lifecycle hooks. May require pre-game configuration.

The Livewire layer (`GameDashboard`, `PlayerPage`, `TeamPage`, `SecretsPage`) is **completely game-agnostic**. It loads whatever Challenge and Modifier classes a Game's template specifies, asks them for UI via `frontendComponent()`, and routes user input back to them via `callClassAction()`. New game modes ship as new Challenge/Modifier classes — no changes to the generic layer.

All state changes go through **Verbs events** (an event-sourcing library). Business logic does not write to the database directly. An event has three lifecycle phases:

- `validate()` — assert preconditions; throws if invalid
- `apply(SomeState $state)` — mutates one or more in-memory state objects (`GameState`, `PlayerState`, `TeamState`, `ChallengeState`, `ModifierState`)
- `handle()` — persists state to Eloquent models (typically `$model->updateModelWithStateData()`)

`Verbs::commit()` flushes pending events. In production code, action handlers fire events and `HandlesClassActions` commits for them. In tests, use `Verbs::commitImmediately()`.

## Directory layout

```
app/
├── Challenges/
│   ├── BaseChallengeClass.php       # abstract base
│   ├── ChallengeRegistry.php        # auto-discovers subclasses
│   ├── IndividualFiller.php         # test utilities (live at root)
│   ├── TeamFiller.php
│   ├── PeckingOrder/                # one folder per game mode
│   │   ├── IndividualBuddySystem.php
│   │   ├── IndividualHighScoreQuiz.php
│   │   └── … (~25 challenges)
│   ├── Farm/
│   ├── Laracon2025/
│   ├── TierList/
│   └── Support/
│       ├── PeckingOrder/            # per-game-mode shared traits + interfaces
│       │   ├── HasPeckingOrderBallots.php
│       │   └── SupportsPeckingOrderBallots.php
│       └── Laracon2025/
│           ├── HasTeamSwaps.php
│           ├── HasTeamPairs.php
│           └── SupportsTeamSwaps.php
├── Modifiers/
│   ├── BaseModifierClass.php
│   ├── ModifierRegistry.php
│   ├── PeckingOrder/                # same per-game-mode pattern
│   ├── Farm/
│   ├── Laracon2025/
│   └── TierList/
├── Events/
│   ├── GameStarted.php              # top-level = game-agnostic lifecycle
│   ├── ChallengeEnded.php
│   ├── PlayerJoinedTeam.php
│   ├── … (many)
│   ├── Traits/                      # HasGame, HasPlayer, HasChallenge, etc.
│   ├── PeckingOrder/                # one folder per game mode for mode-specific events
│   ├── Farm/
│   ├── Laracon2025/
│   └── TierList/
├── States/                          # Verbs state objects
├── Models/                          # Eloquent models
├── Livewire/                        # Livewire components
│   └── Concerns/HandlesClassActions.php
└── Support/
    ├── FormBuilder.php              # fluent UI builder
    ├── FrontendComponentProcessor.php
    ├── FormElementRegistry.php          # auto-discovers form element providers
    └── FormBuilderElements/             # per-mode providers (implement FormElementProvider)
        ├── Farm/FarmFormElements.php
        └── TierList/TierListFormElements.php
```

**Rule of thumb:** a new game mode = a new subfolder under `app/Challenges/`, `app/Modifiers/`, and `app/Events/`. Don't add files to `app/Challenges/Support/` or `app/Modifiers/`'s root — those are per-mode.

## Anatomy of a Challenge

Base class: `app/Challenges/BaseChallengeClass.php`. Read it; it's short.

Every concrete challenge:

1. Lives at `app/Challenges/<GameMode>/<ChallengeName>.php`.
2. Extends `BaseChallengeClass`.
3. Declares constants: `NAME`, `DESCRIPTION`, `TYPE` (`'individual'` or `'team'`), optionally `HIDE_SCOREBOARD`.
4. Implements `public static function key(): string` — a unique key used in the registry, templates, and `round_properties`.
5. Implements `dataArrayForState(): array` — the initial shape of `ChallengeState::$challenge_data` (an arbitrary JSON blob).
6. Implements `frontendComponent(Player $player): array` — returns a built FormBuilder array describing the UI for *this player at this moment*. Conditional UI is normal; use `->when()`.
7. Implements one method per button — signature is always `public function methodName(Player $player, array $params): mixed`. The method fires Verbs events and optionally returns a redirect.
8. Optionally implements lifecycle hooks (see below).
9. Optionally implements support interfaces + uses support traits when sharing logic with other challenges in the same mode (e.g. `implements SupportsPeckingOrderBallots use HasPeckingOrderBallots`).

**Canonical simple example:** `app/Challenges/PeckingOrder/IndividualBuddySystem.php`. Read this end-to-end before writing a new challenge — it shows the fluent FormBuilder, an `onChallengeEnded` hook, conditional UI based on `hasVoted()`, and firing a Verbs event from an action.

**Canonical complex example:** `app/Challenges/TierList/TierListGuess.php`. Shows multi-phase UI, custom blade components, `isInvalidForTemplate()` for template-compatibility validation, and explicit `Verbs::commit()` + broadcast (`GameUpdatedForReverb`) when coordinating across players.

## Anatomy of a Modifier

Base class: `app/Modifiers/BaseModifierClass.php`. Same shape as a challenge, plus:

- `const REQUIRES_PRE_GAME_CONFIGURATION = false|true` — if true, the game admin configures this modifier on `ModifierConfigurationPage` before the game starts.
- Optional `const DEFAULT_CONFIGURATION = [...]` — initial data used during pre-game config.
- Two UI methods: `frontendComponent(Player $player)` (rendered inside `GameDashboard`) and `frontendComponentForDedicatedPage(Player $player)` (rendered on `SecretsPage` or similar). A modifier may use either or both.

**Canonical example without pre-game config:** `app/Modifiers/PeckingOrder/BloodOaths.php`.
**Canonical example with pre-game config:** `app/Modifiers/Laracon2025/TeamSecretCodes.php`.

## Lifecycle hooks

A Challenge can implement any of:

- `onChallengeEnded(GameState $game_state)` — scoring / wrap-up. The most common hook.
- `onPlayerJoinedTeam(PlayerState, TeamState, GameState, ?TeamState $previous_team)` — react to mid-challenge team swaps (only fires for challenges that opt in via `SupportsTeamSwaps`).

A Modifier can implement any of:

- `onGameStarted(GameState, ModifierState)`
- `onChallengeStarted(GameState, ChallengeState, ModifierState)`
- `onChallengeEnded(GameState)`
- `onUserAdmittedToGame(PlayerState, GameState, ModifierState)`
- `onPlayerJoinedTeam(PlayerState, TeamState, GameState, ModifierState, ?TeamState $previous_team)`
- `onSecretDiscovered(Player)`

**Always prefer a lifecycle hook over wiring up a new event listener.** The Verbs events (`PlayerJoinedTeam`, `ChallengeEnded`, etc.) already call these hooks on every active challenge and modifier. If you find yourself reaching for a listener, you're likely fighting the system.

## Verbs events

Read `app/Events/ChallengeEnded.php` for a representative example. Events:

- Use traits from `app/Events/Traits/` (`HasGame`, `HasPlayer`, `HasChallenge`, `HasTeam`, etc.) for state IDs.
- Implement `validate()` — call `$this->assert(condition, 'error message')`.
- Implement `apply()` — one method per state class to mutate (e.g. `applyToGame(GameState $game)`, `applyToChallenge(ChallengeState $c)`).
- Implement `handle()` — persist changes by calling `$model->updateModelWithStateData()` on affected models.

**Top-level events** (`app/Events/*.php`) are game-agnostic and represent the core lifecycle. **Don't add new top-level events for game-specific behavior** — that's a red flag. Add game-specific events under `app/Events/<GameMode>/`.

## State objects

States live in `app/States/`. They are in-memory snapshots that Verbs maintains for fast event application without round-tripping to MySQL.

- `GameState`, `PlayerState`, `TeamState`, `ChallengeState`, `ModifierState` — the main ones.
- Score tracking: `PlayerState::addToScoreHistory(icon, points, description, is_hidden)` and the `score(include_hidden: bool)` method. Hidden points are revealed only at game end.
- `ChallengeState::$challenge_data` and `ModifierState::$modifier_data` are free-form JSON. This is where almost all per-instance state lives.

After firing events, refresh models with `->fresh()` before reading — Eloquent objects are stale until the event's `handle()` writes through.

## Frontend pipeline

Read `app/Support/FormBuilder.php` for the full API. The fluent builder is the **only** supported way to build UI for a challenge or modifier. Do not return raw arrays.

Method categories on FormBuilder:

- **Layout:** `title()`, `subtitle()`, `divider()`, `image()`, `table()`, `when(condition, fn)`, `merge()`, `poll(intervalMs)`.
- **Inputs:** `input()`, `select()`, `radioGroup()`. All take `property_name`, `validation_rules`, `validation_messages`.
- **Buttons:** `buttonGroup()` → one or more `button(label, action, properties_to_validate)` → `endGroup()`. `action` is the method name on the challenge/modifier; `properties_to_validate` lists which form fields validate before this specific button fires.
- **Game-mode-specific:** `peckingOrderBallot()`, `teamSwap()`, `farmMap()`, `farmActions()`, `tierListGuess()`. These come from the per-mode FormBuilder traits and render via custom blade components in `resources/views/components/game-components/custom-form-elements/`. Add a new one only when no built-in primitive fits.
- **Terminator:** `build()` — returns the array consumed by `form.blade.php`.

**The flow** (read `app/Livewire/Concerns/HandlesClassActions.php`):

1. `GameDashboard::mount()` → `initializeProperties()` calls `frontendComponent($player)` on the current challenge and each active modifier, storing results in `$challenge_component` / `$modifier_components[$class_key]`.
2. `FrontendComponentProcessor` walks each component once to populate `round_properties[$class_key][$property_name]` (initial values) and `validation_rules[$class_key]` (rules + messages).
3. `resources/views/components/game-components/form.blade.php` renders each component. Inputs bind to `wire:model="round_properties.{class_key}.{property_name}"`. Buttons emit `wire:click="callClassAction('{action}', '{type}', '{class_key}', {form_json})"`.
4. `callClassAction` looks up the handler, validates the listed properties, invokes `$handler->{$action}($player, $round_properties[$class_key])`, calls `Verbs::commit()`, then `softRefresh()` to re-fetch state and rebuild components.

**Custom blade elements.** When a FormBuilder method emits an element with a `type` the switch in `form.blade.php` doesn't recognize, the blade looks for `game-components.custom-form-elements.{kebab-type}` and renders it dynamically. That's how `farm_map`, `farm_actions`, and `tier_list_guess` work. The pattern: add a method to a per-mode FormBuilder trait, register the trait on `FormBuilder`, create a blade in `custom-form-elements/`. Keep these isolated to one game mode.

## Registries

`app/Challenges/ChallengeRegistry.php` and `app/Modifiers/ModifierRegistry.php` auto-discover their subclasses at boot. You don't register a new class — placing a file in the right directory is enough. The registries:

- Validate `key()` uniqueness (throws on duplicates).
- Provide `retrieveFromModel($key, $model)`, `retrieveFromState($key, $state)`, `retrieveFromKey($key)` for instantiating handlers.
- Provide `options()` for template-builder UIs.

## Score history

The visible "scoreboard" comes from `PlayerState::score()`. The hidden total comes from `PlayerState::score(include_hidden: true)` — used at game end. Each entry has an emoji, a numeric delta, a description, and an `is_hidden` flag. Always add scoring entries via `addToScoreHistory()` inside an `onChallengeEnded` hook or an event's `apply` method; do not mutate scores directly.

## Models ↔ States

Verbs writes state changes to Eloquent models via `$model->updateModelWithStateData()` inside event `handle()` methods. The general rule for reads:

- Reading **inside** an event's `apply()` or a challenge/modifier `on*` hook → use the State object (it's the source of truth at that moment).
- Reading **outside** of event handling (in a Livewire component, controller, etc.) → use the Eloquent model, refreshed with `->fresh()` after any commit.

## When to break ground vs. follow patterns

Default to mirroring an existing class. New patterns are fine when they're genuinely needed — but **keep them scoped to the one challenge or modifier that needs them.** A new custom blade for a custom challenge UI is fine; broadening `form.blade.php` or `BaseChallengeClass` to support it is not.

If you find yourself wanting to add a method to `BaseChallengeClass`, `BaseModifierClass`, `HandlesClassActions`, or `FormBuilder` for a single game-mode use case: stop and ask. Almost every such case has a per-mode trait/interface solution (see `SupportsPeckingOrderBallots` + `HasPeckingOrderBallots` for the pattern).

## Trip-wire files

See `/CLAUDE.md` for the canonical list. Reproduced here for context: stop and ask before modifying `GameDashboard.php`, `PlayerPage.php`, `TeamPage.php`, `BaseChallengeClass.php`, `BaseModifierClass.php`, `ChallengeRegistry.php`, `ModifierRegistry.php`, `HandlesClassActions.php`, `form.blade.php`, `FormBuilder.php`, `FrontendComponentProcessor.php`, or top-level `app/Events/*.php`.

## On staleness

This document and the others under `docs/` are best-effort snapshots. The code in `app/` is the source of truth. When you find a contradiction, fix the doc as part of your PR and call it out in the description.
