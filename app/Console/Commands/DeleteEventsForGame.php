<?php

namespace App\Console\Commands;

use App\Models\Game;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteEventsForGame extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:delete-events-for-game {game_id : The ID of the game to delete events for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete all events for a specific game (DESTRUCTIVE - use with caution)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $gameId = $this->argument('game_id');

        // Verify the game exists
        $game = Game::find($gameId);
        if (! $game) {
            $this->error("Game with ID {$gameId} not found.");

            return 1;
        }

        $this->warn('⚠️  WARNING: This is a DESTRUCTIVE operation!');
        $this->warn("You are about to delete ALL events for game: {$game->name} (ID: {$gameId})");
        $this->newLine();

        // Query the events that will be deleted
        // Use LIKE to search for the game_id in the JSON string
        $eventsCount = DB::table('verb_events')
            ->where('data', 'LIKE', '%"game_id": '.$gameId.'%')
            ->count();

        if ($eventsCount === 0) {
            $this->info('No events found for this game.');

            return 0;
        }

        // Show what will be deleted
        $this->error("⚠️  You will be deleting {$eventsCount} event(s) from the database.");
        $this->error('This action CANNOT be undone!');
        $this->newLine();

        // First confirmation
        if (! $this->confirm('Are you absolutely sure you want to continue?', false)) {
            $this->info('Operation cancelled.');

            return 0;
        }

        // Second confirmation
        $this->warn('This is your FINAL warning!');
        if (! $this->confirm("Type 'yes' again to confirm deletion of {$eventsCount} events", false)) {
            $this->info('Operation cancelled.');

            return 0;
        }

        // Third confirmation - type the game ID
        $this->newLine();
        $confirmGameId = $this->ask("To proceed, please type the game ID ({$gameId}) exactly");

        if ($confirmGameId !== (string) $gameId) {
            $this->error('Game ID did not match. Operation cancelled.');

            return 1;
        }

        // Perform the deletion
        $this->warn('Deleting events...');

        $deletedCount = DB::table('verb_events')
            ->where('data', 'LIKE', '%"game_id": '.$gameId.'%')
            ->delete();

        $this->newLine();
        $this->info("✓ Successfully deleted {$deletedCount} event(s) for game {$gameId}");

        return 0;
    }
}
