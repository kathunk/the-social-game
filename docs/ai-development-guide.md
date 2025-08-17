# AI Development Guide for The Social Game

This document serves as a comprehensive guide for AI assistants working on The Social Game project. It provides essential context about the project structure, conventions, and key components to ensure consistent and informed development.

## Project Overview

The Social Game is a Laravel-based web application focused on social gaming mechanics. It uses modern Laravel features including:

- **Laravel 12.x** - Latest Laravel framework
- **Livewire 3.x** - For reactive UI components
- **Flux UI** - Design system and components
- **Verbs** - Event sourcing library
- **Laravel Cashier** - Stripe payment integration
- **Laravel Horizon** - Queue management
- **Laravel Reverb** - WebSocket server

## Project Structure

### Root Directory Layout
```
the-social-game/
├── app/                    # Laravel application code
├── bootstrap/             # Laravel bootstrap files
├── config/               # Configuration files
├── database/             # Migrations, factories, seeders
├── docs/                 # Project documentation
├── mcp/                  # Model Context Protocol server
├── public/               # Public web assets
├── resources/            # Views, CSS, JS
├── routes/               # Route definitions
├── storage/              # File storage
├── tests/                # Test files
└── vendor/               # Composer dependencies
```

### Application Structure (`app/`)

```
app/
├── Challenges/           # Game challenge logic
├── Console/Commands/     # Artisan commands
├── Events/              # Event sourcing events
├── Http/
│   └── Controllers/     # HTTP controllers
├── Jobs/                # Queue jobs
├── Livewire/           # Livewire components
├── Models/             # Eloquent models
├── Modifiers/          # Game modifiers
├── Observers/          # Model observers
├── Providers/          # Service providers
├── Rules/              # Validation rules
├── States/             # Game state management
└── Support/            # Helper classes
```

### Key Directories Explained

#### `app/Challenges/`
Contains game challenge implementations and logic. Each challenge type has its own class defining rules, progression, and completion conditions.

#### `app/Events/`
Event sourcing events using the Verbs library. These represent state changes in the system:
- User events (creation, promotion, etc.)
- Game events (creation, updates, endings)
- Challenge events (start, completion, etc.)

#### `app/Livewire/`
Livewire components for reactive UI. Major components include:
- `GameDashboard` - Main game interface
- `PreGameLobby` - Game lobby before start
- `CreateGame` - Game creation interface
- `PlayerPage` - Individual player views
- `TeamPage` - Team management

#### `app/Models/`
Core Eloquent models representing the domain:
- `User` - Player accounts
- `Game` - Game instances
- `Challenge` - Game challenges
- `Team` - Player teams
- `Modifier` - Game rule modifications

#### `app/Modifiers/`
Game modifier system allowing dynamic rule changes:
- Team-based modifiers
- Individual player modifiers
- Temporary effect modifiers

## Database Schema Overview

### Core Tables
- `users` - Player accounts and profiles
- `games` - Game instances with settings and state
- `game_players` - Many-to-many relationship between users and games
- `teams` - Player teams within games
- `challenges` - Challenge definitions and instances
- `modifiers` - Game rule modifications
- `events` - Event sourcing event log

### Relationships
- Users can participate in multiple games
- Games can have multiple challenges
- Teams belong to games and contain users
- Modifiers can apply to games, teams, or users
- All state changes are recorded as events

## MCP Integration

The project includes a Model Context Protocol (MCP) server in the `mcp/` directory that provides AI assistants with structured access to game data.

### Available MCP Tools
- `get_active_games` - List active games
- `get_game_details` - Get specific game info
- `get_game_players` - List players in a game
- `get_user_stats` - User statistics
- `get_game_challenges` - Game challenge data

### Available MCP Resources
- `game://active` - Active games resource
- `game://templates` - Game templates
- `stats://leaderboard` - Player leaderboard

## Development Conventions

### PHP/Laravel Conventions
- Follow PSR-12 coding standards
- Use Laravel's naming conventions for models, controllers, etc.
- Event sourcing: All state changes go through Events
- Use Livewire for interactive components
- Implement proper validation using Form Requests and Rules

### Database Conventions
- Use UUIDs for primary keys where appropriate
- Follow Laravel migration naming conventions
- Include proper foreign key constraints
- Use soft deletes for user-generated content

### Frontend Conventions
- Use Flux UI components when available
- Follow Alpine.js patterns for JavaScript interactions
- Use Tailwind CSS for styling
- Maintain responsive design principles

## Key Artisan Commands

### Development Commands
```bash
php artisan serve                    # Start development server
php artisan queue:work              # Process queue jobs
php artisan horizon                 # Start Horizon dashboard
php artisan reverb:start           # Start WebSocket server
```

### Custom Commands
```bash
php artisan create:bots             # Create bot users
php artisan progress:games          # Advance game states
php artisan fill:game-with-bots     # Add bots to games
php artisan fake:laracon-activity   # Generate fake game activity
```

### Data Management
```bash
php artisan db:reset-data           # Reset game data
php artisan verbs:replay-selective  # Replay specific events
php artisan promote:super-admin     # Promote user to admin
```

## Important Configuration

### Environment Variables
- Database connection settings (`DB_*`)
- Stripe integration (`STRIPE_*`)
- Queue configuration (`QUEUE_CONNECTION`)
- Broadcasting (`BROADCAST_DRIVER`)

### Key Config Files
- `config/verbs.php` - Event sourcing configuration
- `config/cashier.php` - Stripe payment settings
- `config/broadcasting.php` - WebSocket configuration

## Game Mechanics

### Game Lifecycle
1. Game creation with templates and modes
2. Player registration and team formation
3. Pre-game lobby phase
4. Active game with challenges
5. Game completion and results

### Challenge System
- Challenges drive game progression
- Multiple challenge types with different mechanics
- Team and individual scoring
- Modifier effects can alter challenge behavior

### Event Sourcing
- All game state changes are events
- Events can be replayed for debugging
- Supports complex game state reconstruction
- Enables advanced analytics and auditing

## Testing Strategy

### Test Structure
```
tests/
├── Feature/              # Feature/integration tests
├── Unit/                # Unit tests
└── Pest.php            # Pest configuration
```

### Testing Tools
- **Pest PHP** - Primary testing framework
- **Laravel Testing** - Built-in Laravel test features
- Database transactions for test isolation

## Deployment Considerations

### Production Setup
- Queue workers for background processing
- Horizon for queue monitoring
- Reverb for WebSocket handling
- Proper caching configuration
- SSL termination
- Database optimization

### Monitoring
- Laravel Pail for log monitoring
- Horizon dashboard for queue metrics
- Custom monitoring for game metrics

## Common Patterns

### Event Sourcing Pattern
```php
// Dispatch events for state changes
UserCreated::dispatch($user);
GameStarted::dispatch($game);
ChallengeCompleted::dispatch($challenge, $user);
```

### Livewire Components
```php
class GameDashboard extends Component
{
    public Game $game;
    
    public function mount(Game $game)
    {
        $this->game = $game;
    }
    
    public function render()
    {
        return view('livewire.game-dashboard');
    }
}
```

### Model Relationships
```php
class Game extends Model
{
    public function players()
    {
        return $this->belongsToMany(User::class, 'game_players');
    }
    
    public function challenges()
    {
        return $this->hasMany(Challenge::class);
    }
}
```

## AI Assistant Guidelines

When working on this project:

1. **Read Architectural Patterns**: First, review [`docs/architectural-patterns.md`](architectural-patterns.md) for core patterns
2. **GameDashboard Centrality**: Most game interactions flow through GameDashboard + HandlesClassActions
3. **Class-Based Logic**: Challenge/Modifier classes define both backend logic AND frontend UI
4. **Event Sourcing**: All state changes must go through Verbs events (validate → apply → handle)
5. **Dynamic Forms**: UI is generated by `frontendComponent()` methods, rendered by form.blade.php
6. **Real-time Updates**: Many features require WebSocket updates and component refreshing
7. **State Consistency**: Keep models and state objects synchronized
8. **Gaming Context**: Always consider social gaming mechanics and fairness
9. **Test Coverage**: Include appropriate tests for new features  
10. **Documentation**: Update guides when adding major features or patterns

## Getting Help

### Key Files to Reference
- `composer.json` - Dependencies and scripts
- `routes/web.php` - Application routes
- `config/app.php` - Application configuration
- Migration files - Database schema
- Model files - Data relationships

### Resources
- [`docs/architectural-patterns.md`](architectural-patterns.md) - Essential reading for core patterns
- Laravel Documentation
- Livewire Documentation  
- Verbs Documentation (Event Sourcing)
- Flux UI Documentation

## Core Architectural Patterns

The project follows specific architectural patterns that define how components interact. For detailed understanding of these patterns, see [`docs/architectural-patterns.md`](architectural-patterns.md).

### Key Pattern: GameDashboard + Challenge/Modifier Classes

The most important pattern in the codebase is the interaction between:

1. **GameDashboard** (Livewire component) - Central UI controller
2. **Challenge Classes** - Extend `BaseChallengeClass`, define game mechanics
3. **Modifier Classes** - Extend `BaseModifierClass`, modify game rules  
4. **form.blade.php** - Renders dynamic forms from class definitions
5. **HandlesClassActions** - Trait that bridges UI interactions to class methods
6. **Verbs Events** - Handle all state changes via event sourcing

### Example Interaction Flow

```php
// 1. User clicks button in form.blade.php
wire:click="callClassAction('submitVote', 'challenge', 'individual_buddy_system')"

// 2. GameDashboard uses HandlesClassActions trait
public function callClassAction($action, $type, $class_key) {
    $handler = $this->getChallengeHandler(); // Gets challenge class instance
    $response = $handler->submitVote($this->player, $properties); // Calls method on class
    Verbs::commit(); // Commits any fired events
}

// 3. Challenge class method fires event
public function submitVote(Player $player, array $properties) {
    PlayerSubmittedPeckingOrderBallot::fire(/* ... */); // Event sourcing
    return redirect()->route('game-dashboard');
}
```

### Frontend Component Pattern

Challenge and Modifier classes generate their own UI:

```php
public function frontendComponent(Player $player): array {
    return [
        'elements' => [
            ['type' => 'title', 'text' => 'Challenge Name'],
            ['type' => 'input', 'property_name' => 'vote_target'],
            ['type' => 'button_group', 'buttons' => [
                ['label' => 'Submit', 'action' => 'submitVote']
            ]]
        ]
    ];
}
```

This guide should be updated as the project evolves to ensure AI assistants have current and accurate information about the codebase.