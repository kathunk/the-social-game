# The Social Game MCP Server

This directory contains the Model Context Protocol (MCP) server for The Social Game Laravel application. The MCP server provides AI assistants with structured access to game data, player information, and game mechanics.

## Overview

The MCP server exposes The Social Game's database and functionality through a standardized interface that AI models can use to:

- Query active games and game details
- Access player information and statistics
- Retrieve game challenges and their status
- Get leaderboard data and game templates
- Perform game-related operations safely
- Understand critical development principles for consistent code

**CRITICAL PRINCIPLES:**
- **Database changes ONLY through Verbs events** - Never direct model updates
- **Livewire + Alpine.js ONLY** - No React/Vue/other frontend frameworks
- **Generic frontend components** - GameDashboard must work with any game content
- **Test everything through Livewire** - Prove frontend integration works

## Architecture

```
mcp/
├── server.js              # Main MCP server implementation
├── tools/                 # Tool-specific implementations (future)
├── resources/            # Resource handlers (future)
├── config/               # Configuration files
├── package.json          # Node.js dependencies
└── README.md            # This file
```

## Setup

### Prerequisites

- Node.js 18+ 
- Access to The Social Game database
- Laravel application configured and running

### Installation

1. Install dependencies:
```bash
cd mcp
npm install
```

2. Ensure your Laravel `.env` file is properly configured with database credentials

3. Test the server:
```bash
npm run dev
```

### Configuration

The MCP server reads database configuration from the parent Laravel application's `.env` file:

- `DB_HOST` - Database host
- `DB_PORT` - Database port  
- `DB_USERNAME` - Database username
- `DB_PASSWORD` - Database password
- `DB_DATABASE` - Database name

## Available Tools

### `get_active_games`
Retrieves all currently active games in the system.

**Parameters:**
- `limit` (optional): Maximum number of games to return (default: 10)
- `offset` (optional): Number of games to skip (default: 0)

**Example:**
```json
{
  "name": "get_active_games",
  "arguments": {
    "limit": 5,
    "offset": 0
  }
}
```

### `get_game_details`
Gets detailed information about a specific game.

**Parameters:**
- `game_id` (required): The ID of the game to retrieve

**Example:**
```json
{
  "name": "get_game_details", 
  "arguments": {
    "game_id": "123"
  }
}
```

### `get_game_players`
Retrieves all players in a specific game.

**Parameters:**
- `game_id` (required): The ID of the game

**Example:**
```json
{
  "name": "get_game_players",
  "arguments": {
    "game_id": "123" 
  }
}
```

### `get_user_stats`
Gets statistics and information for a specific user.

**Parameters:**
- `user_id` (required): The ID of the user

**Example:**
```json
{
  "name": "get_user_stats",
  "arguments": {
    "user_id": "456"
  }
}
```

### `get_game_challenges`
Retrieves challenges for a specific game.

**Parameters:**
- `game_id` (required): The ID of the game
- `status` (optional): Filter by challenge status ('active', 'completed', 'pending')

**Example:**
```json
{
  "name": "get_game_challenges",
  "arguments": {
    "game_id": "123",
    "status": "active"
  }
}
```

## Available Resources

### `game://active`
List of all currently active games in JSON format.

### `game://templates` 
Available game templates and modes in JSON format.

### `stats://leaderboard`
Current player leaderboard with wins and games played.

## Development

### Adding New Tools

1. Define the tool schema in `setupToolHandlers()` method
2. Add the tool handler in the switch statement of `CallToolRequestSchema` handler  
3. Implement the tool method (e.g., `async getMyNewTool(args)`)
4. Update this README with documentation

### Adding New Resources

1. Define the resource in `setupResourceHandlers()` method
2. Add the resource handler in `ReadResourceRequestSchema` handler
3. Implement the resource method (e.g., `async getMyNewResource()`)
4. Update this README with documentation

### Database Queries

The server uses `mysql2/promise` for database access. All queries should:

- Use parameterized queries to prevent SQL injection
- Handle errors gracefully with appropriate MCP error codes
- Return consistent JSON structures

### Error Handling

The server includes comprehensive error handling:

- Database connection errors
- Invalid parameters
- Missing resources/tools  
- SQL execution errors
- Process termination cleanup

## Integration with AI Systems

### Claude Desktop

Add to your Claude Desktop configuration:

```json
{
  "mcpServers": {
    "the-social-game": {
      "command": "node",
      "args": ["server.js"],
      "cwd": "/path/to/the-social-game/mcp"
    }
  }
}
```

### Other MCP Clients

The server follows the MCP specification and should work with any compliant client. Ensure:

- The client can execute Node.js processes
- Database credentials are available in the environment
- The working directory is set correctly

## Security Considerations

- The server only provides read access to game data
- All database queries use parameterized statements
- No direct SQL execution is exposed
- User data is accessed through game relationships only

## Development Principles for AI Assistants

When working with The Social Game codebase, these principles are **non-negotiable**:

### 1. Database Changes ONLY Through Verbs Events

**NEVER** use direct model updates like `Player::update()` or `$player->save()` in business logic:

```php
// ❌ WRONG - Breaks state synchronization
$player->score = 100;
$player->save();

// ✅ CORRECT - Always use events
PlayerScoreChanged::fire(player_id: $player->id, new_score: 100);
Verbs::commit();
```

The entire logical system depends on Verbs states. Direct database updates cause state inconsistency.

### 2. Livewire + Alpine.js ONLY

- **Livewire** for reactive server-side components
- **Alpine.js** for client-side interactivity  
- **NO** React, Vue, or other frontend frameworks

### 3. Generic Frontend Architecture

GameDashboard, PlayerView, and TeamView are completely game-agnostic "superhighways":

- New game modes should work automatically without frontend changes
- Challenge/Modifier classes generate their own UI via `frontendComponent()`
- Only create custom components for special cases (like `tier-list-guess`)

### 4. Test Through Livewire

All features must have Livewire integration tests following established patterns:

```php
Livewire::test(GameDashboard::class, ['game' => $game])
    ->set('round_properties.challenge_key.property', $value)
    ->call('callClassAction', 'action', 'challenge', 'key')
    ->assertHasNoErrors();
```

These principles ensure consistency with the existing codebase and prevent architectural violations.

## Future Enhancements

- [ ] Add write operations for game management
- [ ] Implement caching for frequently accessed data  
- [ ] Add real-time game state updates via WebSocket
- [ ] Expand analytics and reporting tools
- [ ] Add authentication and authorization
- [ ] Implement rate limiting
- [ ] Add comprehensive logging and monitoring

## Troubleshooting

### Common Issues

**Database connection fails:**
- Verify `.env` file contains correct database credentials
- Ensure Laravel application can connect to database
- Check network connectivity and database server status

**Tool execution errors:**
- Check server logs for detailed error messages
- Verify input parameters match expected schema
- Ensure database tables exist and have expected structure

**Resource not found:**
- Verify the resource URI is correctly formatted
- Check that database queries return expected data
- Ensure proper error handling for empty results

### Debugging

Enable verbose logging:
```bash
NODE_ENV=development npm run dev
```

Check database connectivity:
```bash
# From Laravel root directory
php artisan tinker
DB::connection()->getPdo();
```

## Contributing

When making changes to the MCP server:

1. Update tool/resource documentation
2. Test with multiple AI clients if possible
3. Ensure backwards compatibility
4. Update version number in `package.json`
5. Add appropriate error handling
6. Update this README

## License

This MCP server is part of The Social Game project and follows the same license terms.