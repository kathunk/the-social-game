# Testing Guidelines for The Social Game

This document outlines the comprehensive testing approach for The Social Game project, emphasizing Livewire integration tests and following established patterns.

## Table of Contents

- [Testing Philosophy](#testing-philosophy)
- [Test Structure](#test-structure)
- [Testing Patterns](#testing-patterns)
- [Challenge Testing](#challenge-testing)
- [Modifier Testing](#modifier-testing)
- [Event Testing](#event-testing)
- [Livewire Integration Testing](#livewire-integration-testing)
- [Common Test Scenarios](#common-test-scenarios)
- [Test Data Management](#test-data-management)
- [Best Practices](#best-practices)

## Testing Philosophy

The Social Game follows a **Livewire-first testing approach** that emphasizes:

1. **Frontend Integration** - All tests must prove the frontend actually works
2. **Event Sourcing Validation** - Verify events fire correctly and state updates properly  
3. **Game Mechanics Accuracy** - Ensure game rules work as intended
4. **Real-world Simulation** - Tests should mirror actual user interactions

### Why Livewire Integration Tests?

- Proves the entire stack works (frontend + backend + events)
- Catches integration issues that unit tests miss
- Validates the generic component architecture
- Ensures HandlesClassActions trait functions correctly

## Test Structure

All tests use **Pest PHP** and follow this consistent structure:

```php
<?php

use App\Challenges\Classes\MyChallengeClass;
use App\Livewire\GameDashboard;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('tests specific challenge functionality', function () {
    // 1. Enable immediate event processing
    Verbs::commitImmediately();

    // 2. Set up game template
    $this->mockGameTemplate(/* config */);

    // 3. Create game and players
    $game = $this->createGame();
    $players = collect(range(1, 4))->map(fn() => $this->createPlayer());
    $game->start();

    // 4. Test through Livewire
    $this->actingAs($players[0]->user);
    Livewire::test(GameDashboard::class, ['game' => $game->fresh()])
        ->set(/* properties */)
        ->call(/* actions */)
        ->assertHasNoErrors();

    // 5. Assert results
    expect(/* state assertions */);
});
```

## Testing Patterns

### Basic Challenge Test Template

```php
it('runs [challenge name] correctly', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [MyChallengeClass::key()],
            'duration' => 10,
        ],
    ];

    $this->mockGameTemplate(challenges: $challenges, type: 'individual');
    
    $game = $this->createGame();
    $player_1 = $this->createPlayer();
    $player_2 = $this->createPlayer();
    $game->start();

    $challenge = $game->challenges->first();

    // Test player 1 action
    $this->actingAs($player_1->user);
    Livewire::test(GameDashboard::class, ['game' => $game->fresh()])
        ->set("round_properties.{$challenge->class_key}.property_name", $value)
        ->call('callClassAction', 'actionMethod', 'challenge', $challenge->class_key)
        ->assertHasNoErrors();

    // Assert state changes
    expect($player_1->fresh()->score)->toBe(expected_score);
    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(expected_hidden_score);
});
```

### Basic Modifier Test Template

```php
it('applies [modifier name] effects', function () {
    Verbs::commitImmediately();

    $modifiers = [MyModifierClass::key()];
    
    $this->mockGameTemplate(
        challenges: [/* basic challenge */],
        modifiers: $modifiers,
        type: 'individual'
    );

    $game = $this->createGame();
    $player = $this->createPlayer();
    $game->start();

    $modifier = $game->modifiers->first();

    // Test modifier interaction
    $this->actingAs($player->user);
    Livewire::test(GameDashboard::class, ['game' => $game->fresh()])
        ->set("round_properties.{$modifier->class_key}.property", $value)
        ->call('callClassAction', 'modifierAction', 'modifier', $modifier->class_key)
        ->assertHasNoErrors();

    // Assert modifier effects
    expect($modifier->fresh()->modifier_data)->toHaveKey('expected_key');
});
```

## Challenge Testing

### Testing Challenge Lifecycle

```php
it('handles challenge from start to finish', function () {
    Verbs::commitImmediately();

    // Setup
    $this->mockGameTemplate(challenges: [/*...*/]);
    $game = $this->createGame();
    $players = collect(range(1, 3))->map(fn() => $this->createPlayer());
    $game->start();

    $challenge = $game->challenges->first();

    // Test each player's interaction
    $players->each(function ($player) use ($challenge, $game) {
        $this->actingAs($player->user);
        Livewire::test(GameDashboard::class, ['game' => $game->fresh()])
            ->set("round_properties.{$challenge->class_key}.vote_target", $other_player->id)
            ->call('callClassAction', 'submitVote', 'challenge', $challenge->class_key)
            ->assertHasNoErrors();
    });

    // End challenge and test results
    $challenge->refresh();
    $challenge->end();

    // Assert final scores and effects
    $players->each(function ($player, $index) {
        expect($player->fresh()->score)->toBe(expected_scores[$index]);
    });
});
```

### Testing Challenge Validation

```php
it('validates challenge input correctly', function () {
    Verbs::commitImmediately();

    // Setup
    $this->mockGameTemplate(challenges: [/*...*/]);
    $game = $this->createGame();
    $player = $this->createPlayer();
    $game->start();

    $challenge = $game->challenges->first();

    // Test invalid input
    $this->actingAs($player->user);
    Livewire::test(GameDashboard::class, ['game' => $game->fresh()])
        ->set("round_properties.{$challenge->class_key}.invalid_property", 'invalid_value')
        ->call('callClassAction', 'submitAction', 'challenge', $challenge->class_key)
        ->assertHasErrors(); // Should have validation errors
        
    // Test valid input
    Livewire::test(GameDashboard::class, ['game' => $game->fresh()])
        ->set("round_properties.{$challenge->class_key}.valid_property", 'valid_value')
        ->call('callClassAction', 'submitAction', 'challenge', $challenge->class_key)
        ->assertHasNoErrors(); // Should work fine
});
```

## Modifier Testing

### Testing Modifier Configuration

```php
it('configures modifier during pre-game', function () {
    Verbs::commitImmediately();

    $modifiers = [MyConfigurableModifier::key()];
    
    $this->mockGameTemplate(
        challenges: [/*...*/],
        modifiers: $modifiers
    );

    $game = $this->createGame();
    $admin = $game->gameAdmin;
    
    // Test pre-game configuration
    $this->actingAs($admin);
    Livewire::test(ModifierConfigurationPage::class, ['game' => $game])
        ->set('configuration_data.setting_1', 'value_1')
        ->call('saveConfiguration')
        ->assertHasNoErrors();

    $modifier = $game->modifiers->first();
    expect($modifier->fresh()->modifier_data)->toEqual([
        'setting_1' => 'value_1'
    ]);
});
```

### Testing Modifier-Challenge Interactions

```php
it('modifier affects challenge correctly', function () {
    Verbs::commitImmediately();

    $this->mockGameTemplate(
        challenges: [['challenge_keys' => [BasicChallenge::key()]]],
        modifiers: [GameModifyingModifier::key()]
    );

    $game = $this->createGame();
    $player = $this->createPlayer();
    $game->start();

    // Test challenge behavior with modifier active
    $challenge = $game->challenges->first();
    
    $this->actingAs($player->user);
    Livewire::test(GameDashboard::class, ['game' => $game->fresh()])
        ->call('callClassAction', 'challengeAction', 'challenge', $challenge->class_key)
        ->assertHasNoErrors();

    // Assert modifier changed the challenge outcome
    expect($player->fresh()->score)->toBe(modified_expected_score);
});
```

## Event Testing

### Testing Event Validation

```php
it('validates event data correctly', function () {
    Verbs::commitImmediately();

    $game = $this->createGame();
    $player = $this->createPlayer();

    // Test invalid event data
    expect(fn() => MyEvent::fire(
        player_id: $player->id,
        invalid_data: 'bad_value'
    ))->toThrow('Validation error message');

    // Test valid event data
    expect(fn() => MyEvent::fire(
        player_id: $player->id,
        valid_data: 'good_value'
    ))->not->toThrow();
});
```

### Testing Event State Changes

```php
it('applies event state changes correctly', function () {
    Verbs::commitImmediately();

    $game = $this->createGame();
    $player = $this->createPlayer();
    
    $initial_score = $player->score;

    // Fire event
    PlayerScoreChanged::fire(
        player_id: $player->id,
        score_change: 10
    );

    // Assert state changes
    expect($player->fresh()->score)->toBe($initial_score + 10);
    
    // Assert event was recorded
    $events = DB::table('verbs_events')
        ->where('type', PlayerScoreChanged::class)
        ->where('data->player_id', $player->id)
        ->get();
        
    expect($events)->toHaveCount(1);
});
```

## Livewire Integration Testing

### Testing Component Rendering

```php
it('renders challenge component correctly', function () {
    Verbs::commitImmediately();

    $this->mockGameTemplate(challenges: [/*...*/]);
    $game = $this->createGame();
    $player = $this->createPlayer();
    $game->start();

    $this->actingAs($player->user);
    
    Livewire::test(GameDashboard::class, ['game' => $game])
        ->assertSee('Challenge Title')
        ->assertSee('Challenge Description')
        ->assertViewHas('challenge_component');
});
```

### Testing Real-time Updates

```php
it('refreshes on real-time events', function () {
    Verbs::commitImmediately();

    $game = $this->createGame();
    $player = $this->createPlayer();
    $game->start();

    $component = Livewire::test(GameDashboard::class, ['game' => $game])
        ->assertSee('Initial State');

    // Trigger real-time event
    GameUpdatedForReverb::fire(game_id: $game->id);

    // Should redirect/refresh
    $component->call('refreshGame')
        ->assertRedirect(route('game-dashboard', $game));
});
```

## Common Test Scenarios

### Multi-Player Interaction Tests

```php
it('handles multi-player interactions', function () {
    Verbs::commitImmediately();

    $this->mockGameTemplate(challenges: [/*...*/]);
    $game = $this->createGame();
    
    $players = collect(range(1, 4))->map(fn() => $this->createPlayer());
    $game->start();

    $challenge = $game->challenges->first();

    // Each player takes action
    $players->each(function ($player, $index) use ($challenge, $game, $players) {
        $target = $players[($index + 1) % 4]; // Target next player
        
        $this->actingAs($player->user);
        Livewire::test(GameDashboard::class, ['game' => $game->fresh()])
            ->set("round_properties.{$challenge->class_key}.target_player", $target->id)
            ->call('callClassAction', 'targetPlayer', 'challenge', $challenge->class_key)
            ->assertHasNoErrors();
    });

    // End challenge and verify interactions worked
    $challenge->refresh()->end();
    
    // Assert complex multi-player results
    expect($players[0]->fresh()->score)->toBe($expected_scores[0]);
    expect($players[1]->fresh()->score)->toBe($expected_scores[1]);
    // etc...
});
```

### Team-based Challenge Tests

```php
it('handles team challenges correctly', function () {
    Verbs::commitImmediately();

    $this->mockGameTemplate(
        challenges: [/*...*/],
        type: 'team',
        team_names: ['Alpha', 'Beta', 'Gamma']
    );

    $game = $this->createGame();
    $players = collect(range(1, 6))->map(fn() => $this->createPlayer());
    
    // Assign players to teams
    $players->take(2)->each(fn($p) => $p->joinTeam($game->teams[0]));
    $players->skip(2)->take(2)->each(fn($p) => $p->joinTeam($game->teams[1]));
    $players->skip(4)->each(fn($p) => $p->joinTeam($game->teams[2]));
    
    $game->start();

    // Test team-based interactions
    $challenge = $game->challenges->first();
    
    $players->each(function ($player) use ($challenge, $game) {
        $this->actingAs($player->user);
        Livewire::test(GameDashboard::class, ['game' => $game->fresh()])
            ->call('callClassAction', 'teamAction', 'challenge', $challenge->class_key)
            ->assertHasNoErrors();
    });

    // Assert team scores
    expect($game->fresh()->teams[0]->score)->toBe($expected_team_scores[0]);
    expect($game->fresh()->teams[1]->score)->toBe($expected_team_scores[1]);
    expect($game->fresh()->teams[2]->score)->toBe($expected_team_scores[2]);
});
```

## Test Data Management

### Using Test Helpers

```php
// Use established TestCase methods
$this->mockGameTemplate([
    'challenges' => [
        [
            'challenge_keys' => [Challenge1::key(), Challenge2::key()],
            'duration' => 10,
        ]
    ],
    'type' => 'individual',
    'modifiers' => [Modifier1::key()],
    'min_players' => 2,
    'max_players' => 10,
]);

$game = $this->createGame();
$players = collect(range(1, 5))->map(fn() => $this->createPlayer());
```

### Creating Specific Test Scenarios

```php
// Create a game in specific state
function createGameInProgress(array $player_scores = []) {
    $game = $this->createGame();
    $players = collect(range(1, count($player_scores)))->map(fn() => $this->createPlayer());
    
    $game->start();
    
    // Set specific scores if provided
    $players->each(function ($player, $index) use ($player_scores) {
        if (isset($player_scores[$index])) {
            // Use events to set scores
            PlayerScoreChanged::fire(
                player_id: $player->id, 
                score_change: $player_scores[$index]
            );
        }
    });
    
    return [$game, $players];
}
```

## Best Practices

### Test Organization

1. **Group Related Tests** - Use `describe()` blocks for related functionality
2. **Clear Test Names** - Use descriptive `it()` statements
3. **Setup Consistency** - Use `beforeEach()` for common setup
4. **Teardown Properly** - Use `RefreshDatabase` for clean state

### Test Data

1. **Use Factories Sparingly** - Prefer TestCase helper methods
2. **Explicit State** - Be explicit about game/player state setup
3. **Realistic Scenarios** - Test scenarios that match real gameplay
4. **Edge Cases** - Test boundary conditions and error states

### Assertions

1. **Test Both Models and State** - Verify both Eloquent models and Verbs state
2. **Hidden vs Visible Scores** - Test both score types when applicable
3. **UI Feedback** - Assert that UI shows expected content
4. **Error Handling** - Test validation errors and edge cases

### Performance

1. **Use `commitImmediately()`** - For faster test execution
2. **Minimal Data** - Create only necessary test data
3. **Focused Tests** - Each test should verify one specific behavior
4. **Efficient Queries** - Use `fresh()` judiciously to avoid extra queries

## Example Complete Test File

```php
<?php

use App\Challenges\Classes\IndividualBuddySystem;
use App\Livewire\GameDashboard;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Individual Buddy System Challenge', function () {
    beforeEach(function () {
        Verbs::commitImmediately();
        
        $this->mockGameTemplate(
            challenges: [[
                'challenge_keys' => [IndividualBuddySystem::key()],
                'duration' => 10,
            ]],
            type: 'individual'
        );
    });

    it('awards hidden points for mutual upvotes', function () {
        $game = $this->createGame();
        $player_1 = $this->createPlayer();
        $player_2 = $this->createPlayer();
        $player_3 = $this->createPlayer();
        $game->start();

        $challenge = $game->challenges->first();

        // Player 1 upvotes Player 2
        $this->actingAs($player_1->user);
        Livewire::test(GameDashboard::class, ['game' => $game->fresh()])
            ->set("round_properties.{$challenge->class_key}.upvote_player_id", $player_2->id)
            ->set("round_properties.{$challenge->class_key}.downvote_player_id", $player_3->id)
            ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)
            ->assertHasNoErrors();

        // Player 2 upvotes Player 1 (mutual)
        $this->actingAs($player_2->user);
        Livewire::test(GameDashboard::class, ['game' => $game->fresh()])
            ->set("round_properties.{$challenge->class_key}.upvote_player_id", $player_1->id)
            ->set("round_properties.{$challenge->class_key}.downvote_player_id", $player_3->id)
            ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)
            ->assertHasNoErrors();

        $challenge->refresh()->end();

        // Both players should get hidden points for mutual upvote
        expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(2); // 1 visible + 1 hidden
        expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(2); // 1 visible + 1 hidden
        expect($player_3->fresh()->score)->toBe(-2); // Only downvotes, no hidden bonus
    });

    it('does not award hidden points for non-mutual upvotes', function () {
        $game = $this->createGame();
        $player_1 = $this->createPlayer();
        $player_2 = $this->createPlayer();
        $player_3 = $this->createPlayer();
        $game->start();

        $challenge = $game->challenges->first();

        // Player 1 upvotes Player 2
        $this->actingAs($player_1->user);
        Livewire::test(GameDashboard::class, ['game' => $game->fresh()])
            ->set("round_properties.{$challenge->class_key}.upvote_player_id", $player_2->id)
            ->set("round_properties.{$challenge->class_key}.downvote_player_id", $player_3->id)
            ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)
            ->assertHasNoErrors();

        // Player 2 upvotes Player 3 (not mutual)
        $this->actingAs($player_2->user);
        Livewire::test(GameDashboard::class, ['game' => $game->fresh()])
            ->set("round_properties.{$challenge->class_key}.upvote_player_id", $player_3->id)
            ->set("round_properties.{$challenge->class_key}.downvote_player_id", $player_1->id)
            ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)
            ->assertHasNoErrors();

        $challenge->refresh()->end();

        // No hidden points awarded (no mutual upvotes)
        expect($player_1->fresh()->score)->toBe(0); // 1 upvote - 1 downvote
        expect($player_2->fresh()->score)->toBe(1);  // 1 upvote
        expect($player_3->fresh()->score)->toBe(-1); // 1 downvote
        
        // Hidden scores should be the same (no bonus)
        expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(0);
        expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(1);
        expect($player_3->fresh()->state()->score(include_hidden: true))->toBe(-1);
    });
});
```

This comprehensive testing approach ensures that every feature works correctly at the integration level, proving that the generic frontend architecture can handle any game mechanics you create.