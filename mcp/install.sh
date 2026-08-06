#!/bin/bash

# The Social Game MCP Server Installation Script
# This script sets up the MCP server and its dependencies

set -e

echo "🎮 Installing The Social Game MCP Server..."

# Check if we're in the correct directory
if [[ ! -f "../composer.json" ]]; then
    echo "❌ Error: Please run this script from the mcp/ directory within The Social Game project"
    exit 1
fi

# Check Node.js version
echo "📦 Checking Node.js version..."
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js 18+ and try again."
    exit 1
fi

NODE_VERSION=$(node -v | cut -d'v' -f2)
MIN_VERSION="18.0.0"

if [ "$(printf '%s\n' "$MIN_VERSION" "$NODE_VERSION" | sort -V | head -n1)" != "$MIN_VERSION" ]; then
    echo "❌ Node.js version $NODE_VERSION is too old. Please install Node.js 18+ and try again."
    exit 1
fi

echo "✅ Node.js version $NODE_VERSION is compatible"

# Install Node.js dependencies
echo "📦 Installing Node.js dependencies..."
npm install

# Check if Laravel .env exists
echo "🔧 Checking Laravel configuration..."
if [[ ! -f "../.env" ]]; then
    echo "❌ Laravel .env file not found. Please ensure your Laravel application is properly configured."
    exit 1
fi

# Check database configuration
echo "🗄️  Checking database configuration..."
DB_HOST=$(grep "^DB_HOST=" ../.env | cut -d'=' -f2)
DB_DATABASE=$(grep "^DB_DATABASE=" ../.env | cut -d'=' -f2)
DB_USERNAME=$(grep "^DB_USERNAME=" ../.env | cut -d'=' -f2)

if [[ -z "$DB_HOST" || -z "$DB_DATABASE" || -z "$DB_USERNAME" ]]; then
    echo "❌ Database configuration incomplete in .env file"
    echo "Please ensure DB_HOST, DB_DATABASE, and DB_USERNAME are set"
    exit 1
fi

echo "✅ Database configuration found"

# Create logs directory if it doesn't exist
echo "📁 Creating logs directory..."
mkdir -p logs

# Test database connection using Laravel
echo "🔌 Testing database connection..."
cd ..
if php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connection successful';" &> /dev/null; then
    echo "✅ Database connection successful"
else
    echo "❌ Database connection failed. Please check your Laravel database configuration."
    exit 1
fi
cd mcp

# Test MCP server startup
echo "🧪 Testing MCP server startup..."
timeout 5s node server.js > /dev/null 2>&1 || {
    if [[ $? -eq 124 ]]; then
        echo "✅ MCP server starts successfully"
    else
        echo "❌ MCP server failed to start. Check the error messages above."
        exit 1
    fi
}

# Create example configuration for different MCP clients
echo "📋 Creating example configuration files..."

# Claude Desktop configuration
cat > config/claude-desktop-config.json << EOL
{
  "mcpServers": {
    "the-social-game": {
      "command": "node",
      "args": ["server.js"],
      "cwd": "$(pwd)"
    }
  }
}
EOL

# VS Code MCP extension configuration
cat > config/vscode-mcp-config.json << EOL
{
  "mcp.servers": {
    "the-social-game": {
      "command": "node",
      "args": ["server.js"],
      "cwd": "$(pwd)",
      "env": {
        "NODE_ENV": "development"
      }
    }
  }
}
EOL

# Make server executable
chmod +x server.js

echo ""
echo "🎉 Installation complete!"
echo ""
echo "Next steps:"
echo "1. Configure your MCP client to use The Social Game server"
echo "2. For Claude Desktop, add the configuration from config/claude-desktop-config.json to your settings"
echo "3. Test the connection with: npm run dev"
echo ""
echo "Available commands:"
echo "  npm start     - Start the MCP server"
echo "  npm run dev   - Start in development mode with file watching"
echo ""
echo "For more information, see the README.md file in this directory."
