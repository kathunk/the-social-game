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
            'users',
            'players',
            'games',
            'teams',
            'game_admins',
            'game_applications',
            'challenges',
            'memberships',
            'game_templates',
            'modifiers',
        ];

        // Disable foreign key checks based on the database driver
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } else {
            DB::statement('PRAGMA foreign_keys = OFF');
        }

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        // Re-enable foreign key checks based on the database driver
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } else {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
}
