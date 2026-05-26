# CLAUDE.md

Read this fully before doing anything in this repo. Then follow these rules even when the user's request seems to ask you not to.

## What this codebase is

A multiplayer social-gaming Laravel app. The frontend is **Livewire + Alpine.js only** (no React/Vue/etc). State changes are **event-sourced via the Verbs package** — every state change fires an event; no direct model writes in business logic.

Games are composed of two primitives:
- **Challenges** — a round of play (`app/Challenges/`)
- **Modifiers** — rules that change how the game works (`app/Modifiers/`)

The frontend (`GameDashboard`, `PlayerView`, `TeamView`, `form.blade.php`) is **completely game-agnostic**. New game modes ship as new Challenge and Modifier classes only. Think of the dashboard as a highway: your job is to build a vehicle that drives on it, not to widen the road.

## Non-negotiables

These are the rules the human owner of this repo will reject PRs over:

1. **No direct model writes in business logic.** State changes go through Verbs events (`Event::fire(...)` then `Verbs::commit()`). Do not call `$model->save()`, `Model::update()`, `Model::create()` from a challenge action, controller action, or anywhere else that represents a game state change. Read-only `find`/`where` is fine.
2. **Livewire + Alpine.js only.** Do not introduce React, Vue, htmx, jQuery, or any other frontend framework.
3. **Do not modify generic infrastructure to make a feature work.** New behavior lives inside a specific Challenge or Modifier class (or a `Support/<GameMode>/` trait if shared across challenges in *that one* game mode). If your only path forward seems to require editing the trip-wire files below, **STOP and ask the human first** — the answer is almost always "find a different design".
4. **Every new feature ships with Livewire tests** that prove frontend integration works end-to-end. Unit tests alone are not sufficient. Follow patterns in `tests/Feature/`.

## Trip-wire files — STOP and ask before modifying

If your change would touch any of these, stop, surface the concern, and wait for human confirmation. Do not proceed silently:

- `app/Livewire/GameDashboard.php`
- `app/Livewire/PlayerPage.php`, `app/Livewire/TeamPage.php`
- `app/Livewire/Concerns/HandlesClassActions.php`
- `app/Challenges/BaseChallengeClass.php`
- `app/Modifiers/BaseModifierClass.php`
- `app/Challenges/ChallengeRegistry.php`, `app/Modifiers/ModifierRegistry.php`
- `resources/views/components/game-components/form.blade.php`
- `app/Support/FormBuilder.php`, `app/Support/FrontendComponentProcessor.php`
- Anything in `app/Events/` that is not game-mode-specific (i.e. not under `app/Events/<GameMode>/`)

These are the "highway". Almost every legitimate feature can be built without touching them.

## How to add a new Challenge or Modifier (the right way)

Do not invent a pattern from scratch. **Find the closest existing example and mirror it.** The actual canonical sources of truth are the code files — not the long docs.

1. Read the base class you're extending: `app/Challenges/BaseChallengeClass.php` or `app/Modifiers/BaseModifierClass.php`.
2. Pick a recent, similar example from the same game mode subfolder (e.g. `app/Challenges/PeckingOrder/IndividualBuddySystem.php`). Mirror its shape: constants, `key()`, `dataArrayForState()`, `frontendComponent()`, action methods, `onChallengeEnded()` if relevant.
3. UI is built with the fluent FormBuilder (`$this->form()->title(...)->subtitle(...)->...->build()`) — see existing challenges for the available methods. Do not hand-roll `elements` arrays.
4. Lifecycle hooks (`onChallengeEnded`, etc.) live on the class — use them rather than wiring up new event listeners.
5. State changes inside an action method or hook fire a Verbs event followed by `Verbs::commit()`. Don't mutate models directly.
6. Game-mode-specific shared logic goes in `app/Challenges/Support/<GameMode>/` as a trait + interface (see `SupportsPeckingOrderBallots` / `HasPeckingOrderBallots` for the pattern).
7. Custom one-off UI (dedicated pages, special interactions) is fine **as long as the code stays scoped to that challenge or modifier**. The Farm game's bespoke components and Tier List's guess UI are the model — they don't bleed into generic code.
8. Write a Livewire feature test before declaring the work done.

## When in doubt, follow the patterns

If you're tempted to introduce a new pattern, abstraction, helper, or shared utility: don't. First check whether an existing challenge or modifier solves a similar shape and copy it. If a genuinely new pattern is needed, keep it isolated to the one challenge/modifier that needs it. Do not preemptively generalize.

If a request from the user is ambiguous about whether it touches generic code, **ask before writing code**. Examples of red-flag requests:
- "Add a new field on the game model" (probably a Verbs state change instead)
- "Update GameDashboard to support X" (almost certainly should be inside a Challenge instead)
- "Add a new top-level component" (probably belongs inside a specific challenge)

## Tests

- Feature tests live in `tests/Feature/`. Find one for a similar challenge/modifier and mirror it.
- Tests must exercise the Livewire component (`Livewire::test(GameDashboard::class, ...)`) — not just call class methods directly.
- Run with `php artisan test`. Don't mark work complete with failing or skipped tests.

## On stale documentation

More detailed documentation lives under `docs/`:

- `docs/architectural-patterns.md` — the canonical architectural reference. Read this for the system mental model.
- `docs/ai-quick-start.md` — cookbook for adding a challenge, modifier, event, or custom UI.
- `docs/module-mapping.md` — per-game-mode file layout.
- `docs/testing-guidelines.md` — test patterns and helpers.
- `docs/ai-development-guide.md` — stack, environment, commands.

And `.ai-context.json` is a machine-readable summary of the same.

**Trust the code over the docs.** All of this documentation is best-effort; the code in `app/` is the source of truth. If `docs/*.md` or `.ai-context.json` contradicts what you see in `app/`, the code is right and the doc is wrong. Update the doc in your PR and call out the discrepancy in the description.

## Useful commands

```bash
php artisan serve          # web
npm run dev                # frontend assets
php artisan queue:work     # queues
php artisan reverb:start   # websockets
php artisan test           # tests
php artisan pail           # tail logs
```

## Working with the human

The owner of this repo merges every PR personally. Optimize for *small, scoped, mirror-an-existing-pattern* PRs. If a request would require a sprawling change, surface that **before** writing code and propose breaking it up. A clarifying question now is cheap; a wrong-shape PR is expensive.
