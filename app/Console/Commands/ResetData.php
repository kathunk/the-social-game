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
        $this->info('Nullifying circular foreign keys in users table...');
        try {
            DB::table('users')->update([
                'current_game_id' => null,
                'current_player_id' => null,
            ]);
            $this->info('Circular foreign keys nullified successfully');
        } catch (\Exception $e) {
            $this->error('Failed to nullify circular foreign keys: '.$e->getMessage());
        }

        $tables = [
            'memberships',
            'game_admins',
            'game_applications',
            'challenges',
            'modifiers',
            'modifier_configurations',
            'players',
            'teams',
            'games',
            'game_templates',
            'game_modes',
        ];

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } else {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        foreach ($tables as $table) {
            $this->info("Truncating table: {$table}");
            try {
                DB::table($table)->delete();
                DB::table($table)->truncate();
                $count = DB::table($table)->count();
                $this->info("Table {$table} truncated. Remaining records: {$count}");

                if ($count > 0) {
                    $this->error("WARNING: Table {$table} still has {$count} records after truncate!");
                }
            } catch (\Exception $e) {
                dump($e);
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
