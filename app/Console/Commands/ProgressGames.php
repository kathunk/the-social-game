<?php

namespace App\Console\Commands;

use App\Models\Challenge;
use App\Models\Game;
use Illuminate\Console\Command;
use Thunk\Verbs\Facades\Verbs;

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
        Verbs::commitImmediately();

        Game::where('status', 'upcoming')
            ->where('starts_at', '<=', now())
            ->with('gameTemplate')
            ->get()
            ->filter(function (Game $game) {
                return $game->players->count() >= $game->gameTemplate->min_players;
            })
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
