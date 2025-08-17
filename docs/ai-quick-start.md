# AI Assistant Quick Start Guide

This is your starting point for working on The Social Game project. Read this first, then dive deeper into the specific documentation as needed.

## 🎯 What You Need to Know Immediately

### Core Architecture Pattern
Everything revolves around this interaction:
- **GameDashboard** (Livewire) ↔ **Challenge/Modifier Classes** ↔ **Verbs Events** ↔ **form.blade.php**

### Key Files to Understand
1. `app/Livewire/GameDashboard.php` - Central UI controller
2. `app/Challenges/Classes/BaseChallengeClass.php` - Base for all game mechanics
3. `app/Modifiers/Classes/BaseModifierClass.php` - Base for game rule changes
4. `resources/views/components/game-components/form.blade.php` - Dynamic form renderer
5. `app/Livewire/Concerns/HandlesClassActions.php` - UI-to-backend bridge

## 🚀 Quick Development Flow

When adding new game features, you'll typically:

1. **Create a Challenge/Modifier Class** that extends the base class
2. **Define frontend components** via `frontendComponent()` method
3. **Handle user actions** with methods that fire Verbs events (NEVER direct database updates)
4. **The form.blade.php automatically renders** your UI (generic, works with any challenge)
5. **GameDashboard orchestrates** everything via HandlesClassActions
6. **Write comprehensive Livewire tests** to prove frontend integration works

## 📚 Essential Documentation

Read these in order:

1. **[Architectural Patterns](architectural-patterns.md)** - ⭐ MUST READ FIRST
   - Complete understanding of GameDashboard + Challenge/Modifier pattern
   - Event sourcing with Verbs
   - Frontend component lifecycle

2. **[AI Development Guide](ai-development-guide.md)** - Comprehensive project overview
   - Project structure and conventions
   - Development commands and testing
   - Database schema and relationships

3. **[MCP Integration](../mcp/README.md)** - AI data access
   - Model Context Protocol server for querying game data
   - Available tools and resources

## 🔧 Common Tasks & Patterns

### Adding a New Challenge
```php
// 1. Extend BaseChallengeClass
class MyNewChallenge extends BaseChallengeClass 
{
    const NAME = 'My Challenge';
    const DESCRIPTION = 'Challenge description';
    const TYPE = 'individual'; // or 'team'
    
    public static function key(): string { return 'my_new_challenge'; }
    
    // 2. Define UI
    public function frontendComponent(Player $player): array {
        return [
            'elements' => [
                ['type' => 'title', 'text' => static::NAME],
                ['type' => 'input', 'property_name' => 'user_input', 'label' => 'Enter something'],
                ['type' => 'button_group', 'buttons' => [
                    ['label' => 'Submit', 'action' => 'submitAction']
                ]]
            ]
        ];
    }
    
    // 3. Handle user action
    public function submitAction(Player $player, array $properties) {
        // Fire event for state change
        PlayerDidSomething::fire(
            player_id: $player->id,
            game_id: $player->game_id,
            challenge_id: $this->challenge->id,
            user_input: $properties['user_input']
        );
        
        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }
}
```

### Creating a Verbs Event
```php
class PlayerDidSomething extends Event
{
    use HasChallenge, HasGame, HasPlayer;
    
    public string $user_input;
    
    public function validate() {
        $this->assert(!empty($this->user_input), 'Input cannot be empty');
    }
    
    public function apply(ChallengeState $challenge) {
        $challenge->challenge_data['submissions'][$this->player_id] = $this->user_input;
    }
    
    public function handle() {
        Challenge::find($this->challenge_id)->updateModelWithStateData();
    }
}
```

## 🎮 Game Development Context

### This is a Social Gaming Platform
- Multi-player games with real-time interactions
- Team-based and individual challenges
- Hidden point systems and secret mechanics
- Payment integration for premium features
- Event sourcing for complete game state tracking

### Key Gaming Concepts
- **Challenges** - Individual game rounds with specific mechanics
- **Modifiers** - Rules that change how the game works
- **Teams** - Player groups that can be formed/changed during games
- **Hidden Points** - Secret scoring revealed at game end
- **Real-time Updates** - WebSocket-powered live game state

## 🛠 Development Setup

### Start Development Servers
```bash
# Laravel web server
php artisan serve

# Frontend assets
npm run dev

# Queue processing
php artisan queue:work

# WebSocket server (real-time features)
php artisan reverb:start

# MCP server (AI data access)
cd mcp && npm run dev
```

### Useful Commands
```bash
# Create test users/games
php artisan create:bots
php artisan fill:game-with-bots

# Advance game state
php artisan progress:games

# Reset development data
php artisan db:reset-data

# Replay events for debugging
php artisan verbs:replay-selective
```

## 🔍 How to Debug

1. **Check Event Log** - All state changes are in the events table
2. **Use Verbs Replay** - Replay events to debug state issues
3. **Laravel Pail** - Monitor logs in real-time: `php artisan pail`
4. **Horizon Dashboard** - Monitor queues at `/horizon`

## ⚡ Critical Development Principles

**These are NON-NEGOTIABLE and define the entire system:**

1. **Database Changes ONLY Through Verbs Events**
   - NEVER use `$model->save()`, `Model::update()`, etc. in business logic
   - ALL state changes must fire Verbs events followed by `Verbs::commit()`
   - Direct database updates break state synchronization and event sourcing

2. **Livewire + Alpine.js ONLY**
   - No React, Vue, or other frontend frameworks allowed
   - Livewire for reactive server-side components
   - Alpine.js for client-side interactivity

3. **Generic Frontend Architecture**
   - GameDashboard, PlayerView, TeamView are completely game-agnostic
   - They're a "superhighway that accommodates vehicles of all sizes"
   - New game modes should work without frontend code changes

4. **Test Everything Through Livewire**
   - Every feature needs comprehensive Livewire integration tests
   - Follow established patterns in `tests/Feature/`
   - Tests must prove frontend integration actually works

## ⚡ Quick Tips

5. **GameDashboard is the central hub** - Most game interactions flow through it  
6. **Challenge/Modifier classes define UI AND logic** - They're self-contained
7. **Form validation happens automatically** - Define rules in `validationRulesForLivewire()`
8. **Real-time updates are crucial** - Many features need WebSocket updates
9. **Test with bots** - Use artisan commands to quickly populate games

## 🚨 Common Gotchas

- **NEVER** update database directly - only through Verbs events
- Don't forget `Verbs::commit()` after firing events
- Always use `->fresh()` when reloading models after state changes
- GameDashboard must remain generic - don't add game-specific logic
- WebSocket events need proper authentication and authorization  
- Game fairness is critical - prevent cheating in challenge logic
- Hidden points should only be revealed at appropriate times
- Test everything through Livewire - unit tests alone aren't sufficient

## 📖 Next Steps

Once you understand this overview:

1. Read the full [Architectural Patterns](architectural-patterns.md) guide
2. Explore existing Challenge classes in `app/Challenges/Classes/`
3. Look at Verbs events in `app/Events/`
4. Study the GameDashboard implementation
5. Review the form.blade.php component structure

## 🤝 Getting Help

- Check `.ai-context.json` for machine-readable project info
- Use MCP server tools to query game data
- Reference existing patterns in Challenge/Modifier classes
- All documentation is in `docs/` directory

Remember: This is an event-sourced, real-time social gaming platform with a generic frontend architecture. Every interaction should be fun, fair, properly tracked through the event system, and work seamlessly with the existing generic components!