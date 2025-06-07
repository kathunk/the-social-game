<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetData extends Command
{
    protected $signature = 'db:reset-data';

    protected $description = 'Truncates all the data tables. Leaves the Verbs tables alone.';

    public function handle()
    {
        $tables = [
            'memberships',
            'game_admins',
            'game_applications',
            'challenges',
            'players',
            'teams',
            'games',
            'game_templates',
            'modifiers',
        ];

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } else {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        foreach ($tables as $table) {
            $this->info("Truncating table: {$table}");
            try {
                // First try to delete all records
                DB::table($table)->delete();
                // Then truncate to reset auto-increment
                DB::table($table)->truncate();

                // Verify the table is empty
                $count = DB::table($table)->count();
                $this->info("Table {$table} truncated. Remaining records: {$count}");

                if ($count > 0) {
                    $this->error("WARNING: Table {$table} still has {$count} records after truncate!");
                }
            } catch (\Exception $e) {
                $this->error("Failed to truncate {$table}: ".$e->getMessage());
            }
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } else {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        // Clear Redis cache
        $this->info('Clearing Redis cache...');
        try {
            \Illuminate\Support\Facades\Redis::flushall();
            $this->info('Redis cache cleared successfully');
        } catch (\Exception $e) {
            $this->error('Failed to clear Redis cache: '.$e->getMessage());
        }
    }
}
