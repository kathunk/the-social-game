# Testing Guidelines

Every PR ships with feature tests that exercise the change through Livewire. Unit tests alone don't count — this is an event-sourced system, and the only way to prove a feature works is to drive it through the same pipeline a real player would.

## The basics

- **Framework:** Pest. All tests use `it(...)`, `expect(...)`, optionally `beforeEach(...)` and `describe(...)`.
- **DB:** in-memory SQLite (configured in `phpunit.xml`), refreshed between tests via the `RefreshDatabase` trait at the top of every test file.
- **Run:** `php artisan test` (full suite) or `php artisan test --filter=YourTest` (single file or pattern).
- **Location:** all tests live in `tests/Feature/`. There is no `tests/Unit/` — intentionally.

## Setup helpers

These live on the abstract `Tests\TestCase` class (`tests/TestCase.php`) and are available in every test via `$this`.

- `$this->mockGameTemplate(challenges: [...], type: 'individual'|'team', modifiers: [...], team_names: [...], scoreboard_type: ..., ...)` — registers a game mode + template. The `challenges` array is a list of `['challenge_keys' => [...], 'duration' => seconds]`. Sets `$this->game_template` and `$this->game_mode`.
- `$this->createGame()` — creates a game from the most recent template. Sets `$this->game` and `$this->game_admin`. Returns the game.
- `$this->createPlayer()` — creates a user, joins them to `$this->game`, returns the `Player`.

Additional helpers in `tests/Helpers/`:

- `tests/Helpers/ChallengeTestHelpers.php` — `incrementScore(points, ?team, ?player, ?is_hidden)` for setting up score state without running a challenge.
- `tests/Helpers/LivewireTestHelpers.php` — shared Livewire helpers like `swapTeam($player, $team_id, $challenge_key)`.

## Canonical test shape

```php
<?php

use App\Challenges\PeckingOrder\IndividualBuddySystem;
use App\Livewire\GameDashboard;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('awards hidden points for mutual upvotes', function () {
    Verbs::commitImmediately();

    $this->mockGameTemplate(
        challenges: [['challenge_keys' => [IndividualBuddySystem::key()], 'duration' => 10]],
        type: 'individual',
    );

    $game = $this->createGame();
    $player_1 = $this->createPlayer();
    $player_2 = $this->createPlayer();
    $player_3 = $this->createPlayer();
    $game->start();

    $challenge = $game->challenges->first();

    Livewire::actingAs($player_1->user)
        ->test(GameDashboard::class, ['game' => $game->fresh()])
        ->set("round_properties.{$challenge->class_key}.upvote_player_id", $player_2->id)
        ->set("round_properties.{$challenge->class_key}.downvote_player_id", $player_3->id)
        ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)
        ->assertHasNoErrors();

    // (Repeat for player_2 voting for player_1 to complete the mutual upvote...)

    $challenge->refresh()->end();

    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(2);
});
```

The pattern is always:

1. `Verbs::commitImmediately()` — fire events immediately rather than batching.
2. `mockGameTemplate` → `createGame` → `createPlayer(s)` → `$game->start()`.
3. Drive the UI via `Livewire::actingAs($player->user)->test(GameDashboard::class, ['game' => $game->fresh()])`.
4. Set form fields with `->set("round_properties.{class_key}.{property}", $value)`.
5. Call actions with `->call('callClassAction', 'methodName', 'challenge'|'modifier', $class_key)`.
6. Assert with `->assertHasNoErrors()` / `->assertHasErrors()` / `->assertSee('text')`.
7. Refresh models with `->fresh()` before asserting on Eloquent state.

**Reference tests to read end-to-end before writing your own:**

- Simple challenge: `tests/Feature/Challenges/IndividualBuddySystemTest.php` (~90 lines).
- Modifier with multi-player interaction: `tests/Feature/Modifiers/BloodOathTest.php` (~180 lines).
- Direct event firing (no Livewire UI): `tests/Feature/Challenges/PeckingOrderBallotTest.php` (~50 lines).

## What to assert

- **`->assertHasNoErrors()`** on every action you expect to succeed. This is the single most important assertion — it catches validation errors that would otherwise silently fail.
- **`->assertHasErrors()`** when the test is specifically about rejecting invalid input.
- **`->assertSee('text')` / `->assertDontSee('text')`** when the test cares about what the player sees (UI gating, conditional messaging).
- **Model state**: `expect($player->fresh()->score)->toBe(N)`, `expect($challenge->fresh()->challenge_data['votes'])->toHaveKey($player_id)`.
- **Verbs state directly**: `expect($player->fresh()->state()->score(include_hidden: true))->toBe(N)` — use this when testing hidden points or anything the Eloquent model doesn't expose.

## Driving challenges from outside the UI

Sometimes a test needs to advance through the challenge timeline. Two ways:

- **Fast-forward time:** `Date::setTestNow($challenge->ends_at->addSeconds(1));` then `$this->artisan('app:progress-games')`. This runs the same scheduler that runs in prod.
- **End directly:** `$challenge->refresh()->end()` — calls all `onChallengeEnded` hooks and advances to the next challenge.

To fire a Verbs event directly without going through the UI (useful for setting up state):

```php
PlayerSubmittedPeckingOrderBallot::fire(
    player_id: $player_1->id,
    challenge_id: $challenge->id,
    game_id: $game->id,
    upvote_player_id: $player_2->id,
    downvote_player_id: $player_3->id,
);
$challenge->refresh();
```

Don't overuse this — driving through Livewire is what catches the most regressions. Use direct event firing for setup, not as a replacement for UI tests.

## Replay tests

Some challenges have a "can replay" test (see `tests/Feature/Challenges/TeamHotPotatoTest.php`) to verify that `php artisan verbs:replay` reproduces the same final state. Add one when introducing a new game-specific event or complex `apply()` logic — it catches event serialization bugs.

```php
it('can handle a verbs replay', function () {
    Verbs::commit();

    $before = $this->game->fresh()->currentChallenge->challenge_data;

    $this->artisan('db:reset-data');
    $this->artisan('verbs:replay');

    expect($this->game->fresh()->currentChallenge->challenge_data)->toBe($before);
});
```

## What not to do

- **Don't add unit tests in `tests/Unit/`.** This system is stateful and event-driven; isolated unit tests give false confidence. If you're tempted, write a feature test that exercises the same code path through Livewire.
- **Don't mock Verbs (`Verbs::fake()`).** Use `Verbs::commitImmediately()`. Faking breaks the event-sourcing contract — you stop testing whether `apply()` and `handle()` actually work together.
- **Don't bypass `callClassAction` in UI tests.** Calling a handler method directly skips validation and the property pipeline. Drive it through `Livewire::test(GameDashboard::class, ...)`.
- **Don't forget `->fresh()` / `->refresh()`.** Eloquent objects are stale after an event commits until you re-fetch.
- **Don't seed scores by writing to the DB.** Use `incrementScore()` from `tests/Helpers/ChallengeTestHelpers.php` — it goes through the event pipeline.

## Test organization

- New challenges: `tests/Feature/Challenges/<ChallengeName>Test.php`.
- New modifiers: `tests/Feature/Modifiers/<ModifierName>Test.php`.
- New game-mode-level integration: top-level under `tests/Feature/` (e.g. `TierListTest.php`).
- Auth, settings, and onboarding tests already have homes; mirror those.

Use `beforeEach()` to share setup across `it()` blocks in the same file when the template is identical. Use `describe()` blocks for grouping related `it()`s in larger suites.
