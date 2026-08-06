# Quick Start

You're about to add or change something in the game. Before writing code:

1. Read `/CLAUDE.md` if you haven't.
2. Skim `docs/architectural-patterns.md` for the system mental model.
3. **Find the closest existing example and read it end-to-end.** Almost every legitimate feature is "do what `app/Challenges/PeckingOrder/IndividualBuddySystem.php` does, but for X." If you can't find a similar example, your design might be off-pattern — stop and ask.

Below are recipes for the common task shapes.

## Recipe: add a new Challenge

Decide what game mode it belongs to (PeckingOrder, Farm, Laracon2025, TierList, or a new one).

1. Create `app/Challenges/<GameMode>/<ChallengeName>.php`.
2. Extend `App\Challenges\BaseChallengeClass`.
3. Set constants: `NAME`, `DESCRIPTION`, `TYPE` (`'individual'` or `'team'`), optionally `HIDE_SCOREBOARD`.
4. Implement `public static function key(): string` — return a unique snake_case key.
5. Implement `dataArrayForState(): array` — the initial shape of `ChallengeState::$challenge_data` (e.g. `['votes' => [], 'submissions' => []]`).
6. Implement `frontendComponent(Player $player): array` using `$this->form()->...->build()`. Use `->when(condition, fn ($form) => ...)` for conditional UI.
7. Add one method per button: `public function actionName(Player $player, array $params)`. Inside, fire a Verbs event. The `$params` array contains the form fields the user submitted.
8. Implement `onChallengeEnded(GameState $game_state)` for scoring. Use `$game_state->players()->each(...)` and `$player->addToScoreHistory(icon: '🎯', points: 1, description: '...', is_hidden: false)`.
9. If this challenge shares logic with others in the same mode (e.g. ballots): create or use a `Support/<GameMode>/Supports<Thing>` interface + `Has<Thing>` trait.
10. Write a feature test. See `docs/testing-guidelines.md`.

**Canonical reference:** `app/Challenges/PeckingOrder/IndividualBuddySystem.php`. **Mirror its structure.**

## Recipe: add a new Modifier

1. Create `app/Modifiers/<GameMode>/<ModifierName>.php`.
2. Extend `App\Modifiers\BaseModifierClass`.
3. Set constants: `NAME`, `DESCRIPTION`, `TYPE`. Set `REQUIRES_PRE_GAME_CONFIGURATION = true` only if a game admin must configure something before the game starts (then also set `DEFAULT_CONFIGURATION`).
4. Implement `public static function key(): string`.
5. Implement `dataArrayForState(?Game $game = null): array`.
6. Implement either `frontendComponent(Player $player)` (rendered inside `GameDashboard`) or `frontendComponentForDedicatedPage(Player $player)` (rendered on `SecretsPage`) — or both. Return `$this->form()->...->build()`, or `$this->form()->title('...')->build()` if there's nothing for the player to do right now.
7. Add action methods (same signature as challenges).
8. Implement any of the lifecycle hooks: `onGameStarted`, `onChallengeStarted`, `onChallengeEnded`, `onUserAdmittedToGame`, `onPlayerJoinedTeam`, `onSecretDiscovered`. These let the modifier observe and react without wiring up event listeners.
9. Write a feature test.

**Canonical references:** `app/Modifiers/PeckingOrder/BloodOaths.php` (no pre-game config), `app/Modifiers/Laracon2025/TeamSecretCodes.php` (with pre-game config + dedicated page).

## Recipe: add a new Verbs event

Game-specific events live under `app/Events/<GameMode>/`. Game-agnostic lifecycle events live at the top level — **do not** add new top-level events unless you're touching core lifecycle, which is a trip-wire.

1. Create `app/Events/<GameMode>/<EventName>.php` extending `Thunk\Verbs\Event`.
2. Use the relevant traits from `app/Events/Traits/` (`HasGame`, `HasPlayer`, `HasChallenge`, etc.) to declare state IDs.
3. Add public typed properties for any non-trait event data.
4. Implement `validate()` — call `$this->assert(condition, 'Error message')` for preconditions.
5. Implement `apply()` (or `applyToGame`, `applyToChallenge`, etc.) — mutate the relevant state object(s).
6. Implement `handle()` — persist by calling `$model->updateModelWithStateData()` on affected models.
7. Fire it from a challenge/modifier action: `MyEvent::fire(player_id: ..., ...)`. `HandlesClassActions` calls `Verbs::commit()` for you.

**Canonical reference:** any event under `app/Events/PeckingOrder/` (e.g. `PlayerSubmittedPeckingOrderBallot.php`).

## Recipe: add custom UI for a challenge/modifier

If a built-in FormBuilder method doesn't fit (e.g. a drag-drop ranker, a map, a complex SVG view), add a custom element. Keep it scoped to one game mode.

1. Add a provider class at `app/Support/FormBuilderElements/<GameMode>/<Name>FormElements.php` implementing the `App\Support\FormElementProvider` marker interface. Each public method takes `FormBuilder $form` as its first parameter and appends an element array with a unique `type` (snake_case) via `$form->addElement([...])`. Pass all data the blade will need.
2. There is **no registration step** — `FormElementRegistry` auto-discovers every provider in that directory (same pattern as `ChallengeRegistry`), and `FormBuilder::__call` resolves the method by name. Your challenge calls `$this->form()->yourMethod(...)` as if the method lived on FormBuilder. Do not edit `FormBuilder.php`.
3. Create the blade at `resources/views/components/game-components/custom-form-elements/<kebab-type>.blade.php`. Accept `@props(['element'])`, extract data from `$element`, render the UI. Use `wire:model="round_properties.{{ $class_key }}.{{ $element['property_name'] }}"` for two-way binding.
4. `form.blade.php` will pick it up automatically by converting the `type` to kebab-case and rendering via `<x-dynamic-component>`.

**Canonical references:** `app/Support/FormBuilderElements/TierList/TierListFormElements.php` + `resources/views/components/game-components/custom-form-elements/tier-list-guess.blade.php`.

## Recipe: change scoring without changing UI

You usually want this. Don't reach for a new event — instead, change what an existing `onChallengeEnded` or `apply()` method does, or add a new `addToScoreHistory()` call.

## Recipe: add post-game UI (rematch buttons, recaps)

Never add a challenge for this — games end when the last real challenge ends (see architectural-patterns.md, "How games end"). Post-game UI belongs to a modifier:

1. Create a modifier in your mode's folder with a `postGameComponent(Player $player)` method returning a FormBuilder build (custom elements welcome — same pipeline as everything else). `frontendComponent` can return `[]` if the modifier is post-game-only.
2. Store its working data in `ModifierState::$modifier_data` via mode-scoped Verbs events — the challenge is already ended by now, so challenge state is the wrong home.
3. Actions declared on the modifier work normally on the ended-game dashboard via `callClassAction('yourAction', 'modifier', 'your_key', null)`. Guard them with a `game->status === 'ended'` check.
4. Add the modifier's key to your mode's `GameTemplateAdded` `modifiers:` array in the seeder.

**Canonical reference:** `app/Modifiers/ElephantInTheRoom/ElephantRematch.php` + `resources/views/components/game-components/custom-form-elements/elephant-rematch.blade.php`.

## Things that look easy but are actually red flags

- "I'll just add a field to `GameDashboard` for X." → Almost certainly belongs inside a Challenge instead.
- "I'll add a new top-level event for this." → Use a per-game-mode event, or use a lifecycle hook.
- "I'll add a method to `BaseChallengeClass` to share logic between two challenges." → Use a `Support/<GameMode>/Has<X>` trait + `Supports<X>` interface.
- "I'll just call `$model->save()` for speed." → No. Fire a Verbs event.
- "I'll render this with raw blade in `frontendComponent`." → No. Use FormBuilder. If a custom shape is needed, use the custom-element pattern above.

## Development commands

```bash
php artisan serve              # web server
npm run dev                    # frontend assets
php artisan queue:work         # queues
php artisan reverb:start       # websockets (required for real-time updates)
php artisan test               # run tests
php artisan pail               # tail logs
php artisan db:reset-data      # reset local game data
php artisan progress:games     # advance time / end current challenge
php artisan create:bots        # create bot users for testing
php artisan fill:game-with-bots  # populate a game
```

## When to stop and ask

- Anything in the trip-wire list (`/CLAUDE.md`).
- Anything that would require changing more than one game mode's code.
- Anything where the natural design seems to need a new top-level abstraction or a new generic Livewire component.
- Anything where you can't find a similar existing example to mirror.

A clarifying question now is cheap. A wrong-shape PR is expensive.
