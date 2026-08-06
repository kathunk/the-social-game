# The Social Game

A Laravel-based social gaming platform with real-time mechanics, event sourcing, and AI integration through Model Context Protocol (MCP).

## Features

- **Social Gaming**: Multi-player games with team mechanics and challenges
- **Real-time Updates**: WebSocket-powered live game interactions
- **Event Sourcing**: Complete game state tracking and replay capabilities
- **Payment Integration**: Stripe-powered subscriptions and purchases
- **AI Integration**: MCP server for AI assistant access to game data
- **Modern Stack**: Laravel 12, Livewire 3, Flux UI, and Alpine.js

## Quick Start

### Prerequisites

- PHP 8.2+
- Node.js 18+
- MySQL/PostgreSQL
- Composer
- npm

### Installation

1. Clone the repository and install dependencies:
```bash
composer install
npm install
```

2. Configure environment:
```bash
cp .env.example .env
php artisan key:generate
```

3. Set up database:
```bash
php artisan migrate
php artisan db:seed
```

4. Start development servers:
```bash
npm run dev
```

### MCP Server Setup

The Social Game includes a Model Context Protocol server that provides AI assistants with structured access to game data.

1. Install MCP server:
```bash
cd mcp
./install.sh
```

2. Configure your MCP client (e.g., Claude Desktop) using the generated configuration files in `mcp/config/`

3. Test the server:
```bash
npm run dev
```

For detailed MCP documentation, see [`mcp/README.md`](mcp/README.md).

## Development

### Available Commands

```bash
# Laravel development
php artisan serve              # Start web server
php artisan queue:work         # Process background jobs
php artisan horizon           # Queue monitoring dashboard
php artisan reverb:start      # WebSocket server

# Game-specific commands
php artisan create:bots        # Generate bot players
php artisan progress:games     # Advance game states
php artisan fill:game-with-bots # Add bots to games

# Testing
php artisan test              # Run all tests (Pest PHP)

# MCP server
cd mcp && npm run dev         # Start MCP server in development mode
```

### Project Structure

```
the-social-game/
├── app/                      # Laravel application
│   ├── Challenges/          # Game challenge logic
│   ├── Events/              # Event sourcing events
│   ├── Livewire/           # Interactive UI components
│   ├── Models/             # Data models
│   └── Modifiers/          # Game rule modifications
├── mcp/                     # Model Context Protocol server
│   ├── server.js           # MCP server implementation
│   ├── config/             # MCP configuration
│   └── README.md           # MCP documentation
├── docs/                    # Project documentation
└── resources/              # Frontend assets and views
```

## Key Technologies

- **Laravel 12** - PHP framework
- **Livewire 3** - Reactive UI components (ONLY frontend framework used)
- **Alpine.js** - Client-side interactivity
- **Flux UI** - Design system
- **Verbs** - Event sourcing (ONLY way to change database state)
- **Laravel Cashier** - Payment processing
- **Laravel Horizon** - Queue management
- **Laravel Reverb** - WebSocket server
- **Model Context Protocol** - AI integration
- **Pest PHP** - Testing framework

## Documentation

- [AI Quick Start Guide](docs/ai-quick-start.md) - Start here for AI development
- [Architectural Patterns](docs/architectural-patterns.md) - **ESSENTIAL** - Core system patterns
- [AI Development Guide](docs/ai-development-guide.md) - Comprehensive guide for AI assistants
- [MCP Server Documentation](mcp/README.md) - Model Context Protocol integration

## Critical Development Principles

**These principles are non-negotiable and define the entire system:**

1. **Database Changes ONLY Through Verbs Events**
   - NEVER use direct model updates (`$model->save()`, `Model::update()`, etc.)
   - ALL state changes must go through Verbs events
   - Direct database updates break state synchronization

2. **Livewire + Alpine.js ONLY**
   - No React, Vue, or other frontend frameworks
   - Livewire for reactive server-side components
   - Alpine.js for client-side interactivity

3. **Generic Frontend Architecture**
   - GameDashboard, PlayerView, TeamView are completely game-agnostic
   - New game modes should work without frontend changes
   - Components are a "superhighway that accommodates vehicles of all sizes"

4. **Test Everything Through Livewire**
   - Every feature needs Livewire integration tests
   - Follow established testing patterns in `tests/Feature/`
   - Tests must prove frontend integration works

## Contributing

1. **Read the documentation** - Start with [Architectural Patterns](docs/architectural-patterns.md)
2. Fork the repository
3. Create a feature branch
4. Make your changes **following the critical principles above**
5. **Add comprehensive Livewire integration tests**
6. Update documentation
7. Submit a pull request

**Before submitting:**
- Ensure no direct database updates (only Verbs events)
- Verify frontend components work generically
- All tests pass and cover new functionality
- Follow established patterns in existing code

## License

This project is licensed under the MIT License.