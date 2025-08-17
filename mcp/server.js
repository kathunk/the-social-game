#!/usr/bin/env node

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
  CallToolRequestSchema,
  ErrorCode,
  ListResourcesRequestSchema,
  ListToolsRequestSchema,
  McpError,
  ReadResourceRequestSchema,
} from '@modelcontextprotocol/sdk/types.js';
import mysql from 'mysql2/promise';
import dotenv from 'dotenv';
import { fileURLToPath } from 'url';
import { dirname, join } from 'path';

// Load environment variables from Laravel's .env file
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
dotenv.config({ path: join(__dirname, '../.env') });

class TheSocialGameMCPServer {
  constructor() {
    this.server = new Server(
      {
        name: 'the-social-game',
        version: '1.0.0',
      },
      {
        capabilities: {
          resources: {},
          tools: {},
        },
      }
    );

    this.setupToolHandlers();
    this.setupResourceHandlers();
    this.setupErrorHandling();
  }

  async setupDatabase() {
    this.db = await mysql.createConnection({
      host: process.env.DB_HOST || 'localhost',
      port: process.env.DB_PORT || 3306,
      user: process.env.DB_USERNAME,
      password: process.env.DB_PASSWORD,
      database: process.env.DB_DATABASE,
      ssl: process.env.DB_CONNECTION === 'mysql' ? false : undefined,
    });
  }

  setupToolHandlers() {
    this.server.setRequestHandler(ListToolsRequestSchema, async () => {
      return {
        tools: [
          {
            name: 'get_active_games',
            description: 'Retrieve all active games in the system',
            inputSchema: {
              type: 'object',
              properties: {
                limit: {
                  type: 'number',
                  description: 'Maximum number of games to return',
                  default: 10,
                },
                offset: {
                  type: 'number',
                  description: 'Number of games to skip',
                  default: 0,
                },
              },
            },
          },
          {
            name: 'get_game_details',
            description: 'Get detailed information about a specific game',
            inputSchema: {
              type: 'object',
              properties: {
                game_id: {
                  type: 'string',
                  description: 'The ID of the game to retrieve',
                },
              },
              required: ['game_id'],
            },
          },
          {
            name: 'get_game_players',
            description: 'Get all players in a specific game',
            inputSchema: {
              type: 'object',
              properties: {
                game_id: {
                  type: 'string',
                  description: 'The ID of the game',
                },
              },
              required: ['game_id'],
            },
          },
          {
            name: 'get_user_stats',
            description: 'Get statistics for a specific user',
            inputSchema: {
              type: 'object',
              properties: {
                user_id: {
                  type: 'string',
                  description: 'The ID of the user',
                },
              },
              required: ['user_id'],
            },
          },
          {
            name: 'get_game_challenges',
            description: 'Get all challenges for a specific game',
            inputSchema: {
              type: 'object',
              properties: {
                game_id: {
                  type: 'string',
                  description: 'The ID of the game',
                },
                status: {
                  type: 'string',
                  description: 'Filter by challenge status',
                  enum: ['active', 'completed', 'pending'],
                },
              },
              required: ['game_id'],
            },
          },
        ],
      };
    });

    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
      const { name, arguments: args } = request.params;

      try {
        switch (name) {
          case 'get_active_games':
            return await this.getActiveGames(args);

          case 'get_game_details':
            return await this.getGameDetails(args);

          case 'get_game_players':
            return await this.getGamePlayers(args);

          case 'get_user_stats':
            return await this.getUserStats(args);

          case 'get_game_challenges':
            return await this.getGameChallenges(args);

          default:
            throw new McpError(
              ErrorCode.MethodNotFound,
              `Unknown tool: ${name}`
            );
        }
      } catch (error) {
        if (error instanceof McpError) {
          throw error;
        }
        throw new McpError(
          ErrorCode.InternalError,
          `Error executing tool ${name}: ${error.message}`
        );
      }
    });
  }

  setupResourceHandlers() {
    this.server.setRequestHandler(ListResourcesRequestSchema, async () => {
      return {
        resources: [
          {
            uri: 'game://active',
            name: 'Active Games',
            description: 'List of all currently active games',
            mimeType: 'application/json',
          },
          {
            uri: 'game://templates',
            name: 'Game Templates',
            description: 'Available game templates and modes',
            mimeType: 'application/json',
          },
          {
            uri: 'stats://leaderboard',
            name: 'Leaderboard',
            description: 'Current player leaderboard',
            mimeType: 'application/json',
          },
        ],
      };
    });

    this.server.setRequestHandler(ReadResourceRequestSchema, async (request) => {
      const { uri } = request.params;

      try {
        switch (uri) {
          case 'game://active':
            return await this.getActiveGamesResource();

          case 'game://templates':
            return await this.getGameTemplatesResource();

          case 'stats://leaderboard':
            return await this.getLeaderboardResource();

          default:
            throw new McpError(
              ErrorCode.InvalidRequest,
              `Unknown resource: ${uri}`
            );
        }
      } catch (error) {
        if (error instanceof McpError) {
          throw error;
        }
        throw new McpError(
          ErrorCode.InternalError,
          `Error reading resource ${uri}: ${error.message}`
        );
      }
    });
  }

  setupErrorHandling() {
    this.server.onerror = (error) => {
      console.error('[MCP Error]', error);
    };

    process.on('SIGINT', async () => {
      if (this.db) {
        await this.db.end();
      }
      await this.server.close();
      process.exit(0);
    });
  }

  // Tool implementations
  async getActiveGames(args) {
    const limit = args.limit || 10;
    const offset = args.offset || 0;

    const [rows] = await this.db.execute(
      'SELECT id, name, status, created_at, player_count FROM games WHERE status = ? LIMIT ? OFFSET ?',
      ['active', limit, offset]
    );

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({ games: rows }, null, 2),
        },
      ],
    };
  }

  async getGameDetails(args) {
    const [rows] = await this.db.execute(
      'SELECT * FROM games WHERE id = ?',
      [args.game_id]
    );

    if (rows.length === 0) {
      throw new McpError(ErrorCode.InvalidRequest, `Game not found: ${args.game_id}`);
    }

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({ game: rows[0] }, null, 2),
        },
      ],
    };
  }

  async getGamePlayers(args) {
    const [rows] = await this.db.execute(`
      SELECT u.id, u.name, u.email, gp.status, gp.joined_at
      FROM users u
      JOIN game_players gp ON u.id = gp.user_id
      WHERE gp.game_id = ?
    `, [args.game_id]);

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({ players: rows }, null, 2),
        },
      ],
    };
  }

  async getUserStats(args) {
    const [userRows] = await this.db.execute(
      'SELECT * FROM users WHERE id = ?',
      [args.user_id]
    );

    if (userRows.length === 0) {
      throw new McpError(ErrorCode.InvalidRequest, `User not found: ${args.user_id}`);
    }

    const [gameRows] = await this.db.execute(
      'SELECT COUNT(*) as games_played FROM game_players WHERE user_id = ?',
      [args.user_id]
    );

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({
            user: userRows[0],
            stats: {
              games_played: gameRows[0].games_played,
            },
          }, null, 2),
        },
      ],
    };
  }

  async getGameChallenges(args) {
    let query = 'SELECT * FROM challenges WHERE game_id = ?';
    const params = [args.game_id];

    if (args.status) {
      query += ' AND status = ?';
      params.push(args.status);
    }

    const [rows] = await this.db.execute(query, params);

    return {
      content: [
        {
          type: 'text',
          text: JSON.stringify({ challenges: rows }, null, 2),
        },
      ],
    };
  }

  // Resource implementations
  async getActiveGamesResource() {
    const [rows] = await this.db.execute(
      'SELECT id, name, status, created_at, player_count FROM games WHERE status = "active"'
    );

    return {
      contents: [
        {
          uri: 'game://active',
          mimeType: 'application/json',
          text: JSON.stringify({ active_games: rows }, null, 2),
        },
      ],
    };
  }

  async getGameTemplatesResource() {
    const [rows] = await this.db.execute(
      'SELECT * FROM game_templates WHERE archived_at IS NULL'
    );

    return {
      contents: [
        {
          uri: 'game://templates',
          mimeType: 'application/json',
          text: JSON.stringify({ templates: rows }, null, 2),
        },
      ],
    };
  }

  async getLeaderboardResource() {
    const [rows] = await this.db.execute(`
      SELECT u.name, COUNT(gp.id) as games_played,
             SUM(CASE WHEN gp.status = 'winner' THEN 1 ELSE 0 END) as wins
      FROM users u
      LEFT JOIN game_players gp ON u.id = gp.user_id
      GROUP BY u.id, u.name
      ORDER BY wins DESC, games_played DESC
      LIMIT 50
    `);

    return {
      contents: [
        {
          uri: 'stats://leaderboard',
          mimeType: 'application/json',
          text: JSON.stringify({ leaderboard: rows }, null, 2),
        },
      ],
    };
  }

  async run() {
    await this.setupDatabase();

    const transport = new StdioServerTransport();
    await this.server.connect(transport);

    console.error('The Social Game MCP Server running on stdio');
  }
}

// Run the server
const server = new TheSocialGameMCPServer();
server.run().catch((error) => {
  console.error('Failed to run server:', error);
  process.exit(1);
});
