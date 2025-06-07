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
            'players',
            'games',
            'teams',
            'game_admins',
            'game_applications',
            'challenges',
            'memberships',
            'game_templates',
            'modifiers',
            'users',
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }
    }
}
