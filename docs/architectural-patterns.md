# The Social Game - Architectural Patterns Guide

This document captures the core architectural patterns and idiomatic code style for The Social Game project. These patterns demonstrate how GameDashboard, form components, Challenge classes, Modifier classes, and Verbs events work together to create a cohesive system.

## Table of Contents

- [Core Architecture Overview](#core-architecture-overview)
- [The GameDashboard Pattern](#the-gamedashboard-pattern)
- [Challenge Class Architecture](#challenge-class-architecture)
- [Modifier Class Architecture](#modifier-class-architecture)
- [Event Sourcing Patterns](#event-sourcing-patterns)
- [Form Component Integration](#form-component-integration)
- [The HandlesClassActions Pattern](#the-handlesclassactions-pattern)
- [Frontend Component Lifecycle](#frontend-component-lifecycle)
- [Validation Patterns](#validation-patterns)
- [State Management](#state-management)
- [Critical Development Principles](#critical-development-principles)
- [Testing Patterns](#testing-patterns)
- [Best Practices](#best-practices)

## Core Architecture Overview

The Social Game uses a sophisticated architecture that combines:

1. **Livewire Components** - Reactive UI with server-side state
2. **Event Sourcing** - All state changes via Verbs events
3. **Dynamic Class System** - Challenge and Modifier classes with runtime behavior
4. **Form Builder Pattern** - Dynamic form generation from backend classes
5. **State Synchronization** - Seamless sync between models and state objects

### Key Components Interaction

```
GameDashboard (Livewire)
    ├── Challenge Classes (extend BaseChallengeClass)
    │   ├── Generate frontend components
    │   ├── Handle user actions
    │   └── Fire Verbs events
    ├── Modifier Classes (extend BaseModifierClass)
    │   ├── Generate frontend components
    │   ├── Handle user actions
    │   └── Fire Verbs events
    └── form.blade.php (Renders dynamic forms)
        └── Calls HandlesClassActions trait methods
```

## The GameDashboard Pattern

`GameDashboard` is the central Livewire component that orchestrates all game interactions.

### Core Structure

```php
#[On('challenge-complete')]
class GameDashboard extends Component
{
    use HandlesClassActions;

    public Game $game;
    public array $round_properties = [];
    public array $validation_rules = [];
    public ?array $challenge_component = [];
    public ?array $modifier_components = [];

    // Computed properties for reactive data
    #[Computed] public function challenge() { /* ... */ }
    #[Computed] public function modifiers() { /* ... */ }
    #[Computed] public function challengeHandler() { /* ... */ }
}
```

### Key Responsibilities

1. **Component Initialization** - Load challenge and modifier components
2. **Property Management** - Manage form data and validation rules
3. **Action Handling** - Process user interactions via HandlesClassActions
4. **Real-time Updates** - Listen for WebSocket events and refresh

### Initialization Pattern

```php
protected function initializeProperties()
{
    // Initialize challenge component
    $this->challenge_component = $this->game->currentChallenge
        ?->fresh()
        ->handler()
        ->frontendComponent($this->player);

    // Initialize challenge properties and validation
    if ($this->challenge) {
        $this->round_properties[$this->challenge->class_key] =
            $this->challenge_handler?->propertiesForLivewire($this->player) ?? [];
        $this->validation_rules[$this->challenge->class_key] =
            $this->challenge_handler?->validationRulesForLivewire($this->player) ?? [];
    }

    // Initialize modifier components
    foreach ($this->modifiers as $modifier) {
        $this->round_properties[$modifier->class_key] =
            $modifier->handler()?->propertiesForLivewire($this->player) ?? [];
        
        $this->validation_rules[$modifier->class_key] =
            $modifier->handler()?->validationRulesForLivewire($this->player) ?? [];
            
        $this->modifier_components[$modifier->class_key] = 
            $modifier->handler()->frontendComponent($this->player);
    }
}
```

## Challenge Class Architecture

Challenge classes extend `BaseChallengeClass` and define game mechanics.

### Base Structure Pattern

```php
abstract class BaseChallengeClass
{
    const NAME = 'Challenge Name';
    const DESCRIPTION = 'Challenge description with {dynamic} placeholders';
    const TYPE = 'team'; // or 'individual'
    const HIDE_SCOREBOARD = false;

    abstract public static function key(): string;

    public ?Player $player = null;
    public ?PlayerState $player_state = null;
    
    public function __construct(
        public ?Challenge $challenge = null,
        public ?ChallengeState $challenge_state = null
    ) {}
}
```

### Implementation Pattern

```php
class IndividualBuddySystem extends BaseChallengeClass implements SupportsPeckingOrderBallots
{
    use HasPeckingOrderBallots;

    const NAME = 'Buddy System';
    const DESCRIPTION = 'If the player you upvote this round also upvotes you, you will both receive a hidden point.';
    const TYPE = 'individual';

    public static function key(): string
    {
        return 'individual_buddy_system';
    }

    // Define frontend component structure
    public function frontendComponent(Player $player): array
    {
        return [
            'elements' => [
                [
                    'type' => 'title',
                    'text' => static::NAME,
                ],
                [
                    'type' => 'message',
                    'text' => $this->interpolatedDescription($player),
                ],
                // Form elements...
                [
                    'type' => 'button_group',
                    'buttons' => [
                        [
                            'label' => 'Submit Vote',
                            'action' => 'submitVote',
                            'properties_to_validate' => ['upvote_player_id', 'downvote_player_id']
                        ]
                    ]
                ]
            ]
        ];
    }

    // Handle user actions - fires events
    public function submitVote(Player $player, array $properties)
    {
        PlayerSubmittedPeckingOrderBallot::fire(
            player_id: $player->id,
            game_id: $player->game_id,
            challenge_id: $this->challenge->id,
            upvote_player_id: $properties['upvote_player_id'],
            downvote_player_id: $properties['downvote_player_id']
        );

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    // Handle challenge completion
    public function onChallengeEnded(GameState $game_state)
    {
        // Process buddy system logic
        // Award hidden points for mutual upvotes
    }
}
```

### Challenge Class Capabilities

1. **Frontend Component Generation** - Define UI dynamically
2. **Action Handling** - Process user interactions
3. **Event Firing** - Trigger state changes via Verbs
4. **Lifecycle Hooks** - React to game events
5. **Validation** - Define form validation rules
6. **State Queries** - Access current game/player state

## Modifier Class Architecture

Modifier classes extend `BaseModifierClass` and modify game rules.

### Base Structure

```php
abstract class BaseModifierClass
{
    const NAME = 'Modifier Name';
    const DESCRIPTION = 'Modifier description';
    const TYPE = 'team'; // or 'individual'
    const REQUIRES_PRE_GAME_CONFIGURATION = false;

    abstract public static function key(): string;

    public function __construct(
        public ?Modifier $modifier = null,
        public ?ModifierState $modifier_state = null
    ) {}
}
```

### Implementation Pattern

```php
class BloodOaths extends BaseModifierClass
{
    const NAME = 'Blood Oaths';
    const DESCRIPTION = 'Create an unbreakable alliance with another player, and play the game together in secret. Or, gain hidden points for making an oath of solitude.';
    const TYPE = 'individual';

    public static function key(): string
    {
        return 'blood_oaths';
    }

    public function frontendComponent(Player $player): array
    {
        $oath_data = $this->modifier_state->modifier_data ?? [];
        $has_partner = isset($oath_data['pairs'][$player->id]);

        if ($has_partner) {
            return []; // No UI needed if already paired
        }

        return [
            'elements' => [
                [
                    'type' => 'title',
                    'text' => 'Form Blood Oath',
                ],
                [
                    'type' => 'select',
                    'label' => 'Choose Partner',
                    'property_name' => 'partner_id',
                    'options' => $this->getAvailablePartners($player),
                    'searchable' => true
                ],
                [
                    'type' => 'button_group',
                    'buttons' => [
                        [
                            'label' => 'Form Oath',
                            'action' => 'formOath',
                            'properties_to_validate' => ['partner_id']
                        ],
                        [
                            'label' => 'Oath of Solitude',
                            'action' => 'formSolitudeOath'
                        ]
                    ]
                ]
            ]
        ];
    }

    public function formOath(Player $player, array $properties)
    {
        PlayerOfferedBloodOath::fire(
            player_id: $player->id,
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            target_player_id: $properties['partner_id']
        );
    }

    public function onChallengeEnded(GameState $game_state)
    {
        // Apply blood oath effects to challenge results
    }
}
```

## Event Sourcing Patterns

All state changes in the system happen through Verbs events.

### Event Structure Pattern

```php
class PlayerSubmittedPeckingOrderBallot extends Event
{
    use HasChallenge, HasGame, HasPlayer;

    public int $downvote_player_id;
    public int $upvote_player_id;

    // Validation before event is applied
    public function validate()
    {
        $this->assert(
            $this->state(GameState::class)->player_ids->contains($this->downvote_player_id),
            'Downvote player is not in the game'
        );
        
        $this->assert(
            $this->upvote_player_id !== $this->downvote_player_id,
            'Cannot vote for the same player'
        );
    }

    // Apply to state objects
    public function apply(ChallengeState $challenge)
    {
        $challenge->challenge_data['votes'][$this->player_id] = [
            'downvote_player_id' => $this->downvote_player_id,
            'upvote_player_id' => $this->upvote_player_id
        ];
    }

    // Update Eloquent models
    public function handle()
    {
        Challenge::find($this->challenge_id)->updateModelWithStateData();
    }
}
```

### Event Traits Pattern

```php
trait HasGame
{
    #[StateId(GameState::class)]
    public int $game_id;

    public function game()
    {
        return Game::find($this->game_id);
    }
}

trait HasPlayer
{
    #[StateId(PlayerState::class)]
    public int $player_id;

    public function player()
    {
        return Player::find($this->player_id);
    }
}
```

### Complex Event Pattern

```php
class PlayerJoinedTeam extends Event
{
    use HasActiveGame, HasActivePlayer, HasTeam;

    public ?int $previous_team_id = null;

    public function validate()
    {
        // Validate team swap rules
        if ($this->previous_team_id && $this->state(GameState::class)->current_challenge_id) {
            $this->assert(
                $this->state(GameState::class)->currentChallenge()->handler()->supportsTeamSwaps(),
                'Challenge does not support team swaps'
            );
        }
    }

    public function applyToGame(GameState $game)
    {
        // Notify challenge handler
        $game->currentChallenge()->handler()->onPlayerJoinedTeam(
            player_state: $this->state(PlayerState::class),
            team_state: $this->state(TeamState::class),
            game_state: $game,
            previous_team: $this->previous_team_id ? TeamState::load($this->previous_team_id) : null
        );

        // Notify all modifier handlers
        $game->modifiers()->each(fn (ModifierState $modifier) => 
            $modifier->handler()->onPlayerJoinedTeam(/* ... */)
        );
    }

    public function handle()
    {
        // Update multiple models and sync states
        $player = Player::find($this->player_id);
        $player->team_id = $this->team_id;
        $player->save();

        $game = Game::find($this->game_id);
        $game->teams->each(fn ($t) => $t->updateModelWithStateData());
    }
}
```

## Form Component Integration

The `form.blade.php` component renders dynamic forms generated by Challenge and Modifier classes.

### Form Structure Pattern

```php
@props(['form', 'type' => null, 'class_key'])

@if (isset($form['elements']))
<x-card>
    <div class="flex flex-col space-y-1">
        @foreach ($form['elements'] as $element)
            @switch($element['type'])
                @case('title')
                    <x-forms.heading>{{ $element['text'] }}</x-forms.heading>
                    @break
                    
                @case('input')
                    <flux:input
                        wire:key="input-{{ $class_key }}-{{ $element['property_name'] }}"
                        label="{{ $element['label']}}"
                        placeholder="{{ $element['placeholder']}}"
                        wire:model="round_properties.{{ $class_key }}.{{ $element['property_name']}}"
                    />
                    @break
                    
                @case('button_group')
                    <div class="flex flex-wrap gap-2 mt-4 justify-end">
                        @foreach ($element['buttons'] as $btn)
                            <x-button
                                wire:loading.attr="disabled"
                                wire:key="button-{{ $class_key }}-{{ $btn['action'] }}"
                                variant="primary"
                                wire:click="callClassAction('{{ $btn['action'] }}', '{{ $type }}', '{{ $class_key }}', {{ json_encode($form) }})"
                            >
                                {{ $btn['label'] }}
                            </x-button>
                        @endforeach
                    </div>
                    @break
            @endswitch
        @endforeach
    </div>
</x-card>
@endif
```

### Frontend Component Element Types

- **title** - Heading text
- **subtitle** - Subheading text
- **message** - Informational text
- **input** - Text input (size: 'large' for textarea)
- **select** - Dropdown with options and searchable flag
- **button_group** - Action buttons with validation
- **table** - Data tables with headers and rows
- **divider** - Visual separator
- **image** - Images with alt text
- **tier_list_guess** - Custom tier list component

## The HandlesClassActions Pattern

The `HandlesClassActions` trait provides the bridge between frontend interactions and backend class methods.

### Action Flow

1. User clicks button in form component
2. `callClassAction()` method is invoked
3. Validation rules are applied
4. Appropriate handler method is called
5. Verbs events are fired
6. Components are refreshed

### Implementation Pattern

```php
trait HandlesClassActions
{
    public function callClassAction(string $action, string $type, string $class_key, ?array $component = null)
    {
        $params = $this->round_properties[$class_key];
        
        $handler = match ($type) {
            'challenge' => $this->getChallengeHandler(),
            'modifier' => $this->getModifierHandler($class_key),
        };

        // Validate specific fields for this action
        $button = $this->findButtonInComponent($component, $action);
        $fields = $button['properties_to_validate'] ?? [];
        
        if (!empty($fields)) {
            $this->validateActionFields($class_key, $fields);
        }

        // Call the handler method
        $response = $handler->{$action}($this->player, $params);
        
        Verbs::commit(); // Commit events to state
        
        $this->updateComponentsAfterAction($type, $class_key);
        
        return $response; // Handle redirects
    }
}
```

## Frontend Component Lifecycle

### Component Generation Process

1. **Class Instantiation** - Challenge/Modifier class is instantiated
2. **State Loading** - Current game/player state is loaded
3. **Component Building** - `frontendComponent()` generates form structure
4. **Property Extraction** - `propertiesForLivewire()` extracts form data
5. **Validation Setup** - `validationRulesForLivewire()` sets up validation
6. **Rendering** - Blade component renders the form

### Dynamic Property Interpolation

```php
protected function interpolatedDescription(Player $player): string
{
    $description = static::DESCRIPTION;
    
    // Replace {player} with player name
    $description = str_replace('{player}', $player->name, $description);
    
    // Replace {score} with current score
    $description = str_replace('{score}', $player->score, $description);
    
    // Replace game-specific placeholders
    $game_state = GameState::load($player->game_id);
    $description = str_replace('{average}', $game_state->averageScore(), $description);
    
    return $description;
}
```

## Validation Patterns

### Frontend Component Validation

```php
public function validationRulesForLivewire(Player $player): array
{
    return [
        'rules' => [
            'partner_id' => 'required|exists:players,id',
            'vote_type' => 'required|in:upvote,downvote'
        ],
        'messages' => [
            'partner_id.required' => 'You must select a partner',
            'partner_id.exists' => 'Selected partner is not valid'
        ]
    ];
}
```

### Dynamic Validation Based on State

```php
public function validationRulesForLivewire(Player $player): array
{
    $rules = ['vote_strength' => 'required|integer|min:1'];
    
    // Modify validation based on current game state
    if ($this->playerHasDoubleVotePower($player)) {
        $rules['vote_strength'] .= '|max:4'; // Allow double strength
    } else {
        $rules['vote_strength'] .= '|max:2'; // Normal strength
    }
    
    return ['rules' => $rules];
}
```

## State Management

### State Object Pattern

```php
class GameState extends State
{
    public int $id;
    public string $status;
    public ?int $current_challenge_id = null;
    public Collection $player_ids;
    public Collection $team_ids;
    public array $game_data = [];

    public function currentChallenge(): ?ChallengeState
    {
        return $this->current_challenge_id 
            ? ChallengeState::load($this->current_challenge_id)
            : null;
    }

    public function modifiers(): Collection
    {
        return collect($this->game_data['modifier_ids'] ?? [])
            ->map(fn ($id) => ModifierState::load($id));
    }
}
```

### Model-State Synchronization

```php
public function updateModelWithStateData()
{
    $state = GameState::load($this->id);
    
    $this->status = $state->status;
    $this->current_challenge_id = $state->current_challenge_id;
    $this->game_data = $state->game_data;
    
    $this->save();
}
```

## Best Practices

### Challenge Class Best Practices

1. **Immutable Constants** - Use const for NAME, DESCRIPTION, TYPE
2. **Key Uniqueness** - Ensure static::key() returns unique identifiers
3. **State Safety** - Always validate state before applying changes
4. **Event Driven** - Use events for ALL state modifications - never direct model updates
5. **Generic UI** - frontendComponent() should work with generic form.blade.php
6. **Lifecycle Awareness** - Implement lifecycle hooks appropriately
7. **Test Coverage** - Every challenge needs Livewire integration tests

### Modifier Class Best Practices

1. **Minimal UI** - Only show forms when player action is needed
2. **State Persistence** - Store configuration in modifier_data
3. **Game Integration** - React to game events via lifecycle hooks
4. **Conflict Resolution** - Handle interactions with other modifiers
5. **Event Driven** - Never update models directly, always use events
6. **Generic Compatibility** - Work with GameDashboard without custom frontend code
7. **Test Coverage** - Every modifier needs Livewire integration tests

### Event Best Practices

1. **Validation First** - Always validate before applying
2. **Atomic Operations** - Each event should be a single logical change
3. **State Consistency** - Update both state objects and models
4. **Error Handling** - Use assertions for business rule validation
5. **Only Path for Changes** - Events are the ONLY way to modify game state
6. **Test Coverage** - Every event needs comprehensive test coverage

### Frontend Component Best Practices

1. **Clear Structure** - Use consistent element types
2. **Validation Mapping** - Map validation to specific form fields
3. **User Experience** - Provide clear labels and feedback
4. **Performance** - Use wire:key for efficient re-rendering
5. **Generic Design** - Components must work with any challenge/modifier
6. **Livewire Integration** - All interactions via wire:model and wire:click

### Livewire Integration Best Practices

1. **Component Isolation** - Keep components focused and cohesive
2. **Real-time Updates** - Use WebSocket events for live updates
3. **State Hydration** - Properly initialize component state
4. **Memory Management** - Clean up unused properties
5. **Generic Architecture** - Components must work with any game content
6. **No Direct DB Updates** - All changes via events and Verbs::commit()
7. **Alpine Integration** - Use Alpine.js for client-side interactivity
8. **Test Through Livewire** - Prove frontend integration with comprehensive tests

## Critical Development Principles

These principles are **non-negotiable** and define the entire system's architecture:

### 1. Database Changes ONLY Through Verbs Events

**NEVER** directly update models in business logic. The entire system depends on Verbs state consistency.

```php
// ❌ WRONG - This breaks state synchronization
$player = Player::find($id);
$player->score = 100;
$player->save();

// ✅ CORRECT - Always use events
PlayerScoreChanged::fire(
    player_id: $player->id,
    new_score: 100
);
Verbs::commit();
```

**Why this matters:**
- Verbs State objects become out of sync when you bypass events
- Event sourcing depends on ALL changes being recorded
- Replay functionality breaks with direct database updates
- State reconstruction becomes impossible

### 2. Livewire + Alpine Only

**No React, Vue, or other frontend frameworks.** The system is designed around:
- **Livewire** for reactive server-side components
- **Alpine.js** for client-side interactivity
- **Flux UI** components for consistent design

```php
// ✅ Livewire component structure
class GameDashboard extends Component
{
    use HandlesClassActions;
    
    public Game $game;
    public array $round_properties = [];
    
    #[Computed]
    public function challenge() { /* ... */ }
}
```

### 3. Generic Frontend Components

GameDashboard, PlayerView, and TeamView must remain **completely agnostic** to game content. They are a "superhighway that can accommodate vehicles of all sizes."

```php
// ✅ Generic component that works with any challenge/modifier
protected function initializeProperties()
{
    // This works for ANY challenge class
    $this->challenge_component = $this->game->currentChallenge
        ?->fresh()
        ->handler()
        ->frontendComponent($this->player);
        
    // This works for ANY modifier class  
    foreach ($this->modifiers as $modifier) {
        $this->modifier_components[$modifier->class_key] = 
            $modifier->handler()->frontendComponent($this->player);
    }
}
```

**Key principle:** When you create a new game mode/template, the frontend should handle it automatically without code changes (except for custom components like `tier-list-guess`).

### 4. Test Everything Through Livewire

Every feature must have tests that prove the frontend integration works, following established conventions:

```php
// ✅ Test pattern - Through Livewire to prove frontend works
Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
    ->set('round_properties.'.$challenge->class_key.'.upvote_player_id', $player_2->id)
    ->set('round_properties.'.$challenge->class_key.'.downvote_player_id', $player_3->id)
    ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)
    ->assertHasNoErrors();
```

## Testing Patterns

### Test Structure Convention

All tests follow this pattern using the established TestCase helpers:

```php
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('tests specific functionality', function () {
    Verbs::commitImmediately();

    // 1. Set up game template with challenges/modifiers
    $this->mockGameTemplate(
        challenges: [['challenge_keys' => [MyChallenge::key()], 'duration' => 10]],
        type: 'individual',
        modifiers: [MyModifier::key()]
    );

    // 2. Create game and players
    $game = $this->createGame();
    $player_1 = $this->createPlayer();
    $player_2 = $this->createPlayer();
    $game->start();

    // 3. Test through Livewire
    $this->actingAs($player_1->user);
    Livewire::test(GameDashboard::class, ['game' => $game->fresh()])
        ->set('round_properties.'.$class_key.'.property_name', $value)
        ->call('callClassAction', 'actionName', 'challenge', $class_key)
        ->assertHasNoErrors();

    // 4. Assert results via state/models
    expect($player_1->fresh()->score)->toBe(expected_value);
});
```

### Test Helper Methods

Use the established TestCase methods:

- `$this->mockGameTemplate()` - Creates game templates with challenges/modifiers
- `$this->createGame()` - Creates a game instance
- `$this->createPlayer()` - Creates and joins a player to the game
- `Verbs::commitImmediately()` - For immediate event processing in tests

### Testing Both Visible and Hidden Scores

```php
// Test visible scores
expect($player->fresh()->score)->toBe(10);

// Test hidden scores (state-based)
expect($player->fresh()->state()->score(include_hidden: true))->toBe(15);
```

### Testing Frontend Integration

Always test that the frontend components render correctly:

```php
Livewire::actingAs($player->user)->test(GameDashboard::class, ['game' => $game->fresh()])
    ->assertSee('Expected UI Text')
    ->assertDontSee('Text That Should Not Appear')
    ->assertViewHas('expected_property');
```

This architectural pattern creates a highly flexible, event-driven gaming system where game mechanics can be defined declaratively in Challenge and Modifier classes, rendered dynamically in the frontend, and processed through a consistent event sourcing pipeline.