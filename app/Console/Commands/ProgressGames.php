<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\Challenge;
use Illuminate\Console\Command;

class ProgressGames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:progress-games';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Game::where('status', 'upcoming')
            ->where('starts_at', '<=', now())
            ->each(function (Game $game) {
                $game->start();
            });

        Challenge::where('status', 'active')
            ->where('ends_at', '<=', now())
            ->each(function (Challenge $challenge) {
                $challenge->end();
            });

        Challenge::where('status', 'upcoming')
            ->where('starts_at', '<=', now())
            ->each(function (Challenge $challenge) {
                $challenge->start();
            });

        Game::where('status', 'active')
            ->where('ends_at', '<=', now())
            ->each(function (Game $game) {
                $game->end();
            });
    }
}
