<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\Challenge;
use App\Events\GameUpdated;
use App\Jobs\StartGameIfReady;
use Thunk\Verbs\Facades\Verbs;
use App\Jobs\ProgressChallenge;
use Illuminate\Console\Command;
use App\Events\GameUpdatedForReverb;

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
                $min = $game->gameTemplate->min_players;
                $max = $game->gameTemplate->max_players;
                $playerCount = $game->players->count();

                $playerCountIsOk = true;

                if ($min) {
                    $playerCountIsOk = $playerCountIsOk && ($playerCount >= $min);
                }

                if ($max) {
                    $playerCountIsOk = $playerCountIsOk && ($playerCount <= $max);
                }

                return $playerCountIsOk;
            })
            ->each(function (Game $game) {
                // this accounts for an edge case where the game was scheduled in the past, but they
                // had the wrong player count. Then they fixed the problem, and we want to be sure the
                // game has a sensible start time.

                if ($game->starts_at < now()->subMinutes(2)) {
                    GameUpdated::fire(
                        game_id: $game->id,
                        game_template_id: $game->game_template_id,
                        starts_at: now(),
                        ends_at: now()->addMinutes($game->gameTemplate->total_duration),
                        is_public: $game->is_public,
                        requires_admin_approval_to_join: $game->requires_admin_approval_to_join,
                    );
                }

                Verbs::commit();

                StartGameIfReady::dispatch($game->fresh());
            });

        Challenge::where('status', 'active')
            ->where('ends_at', '<=', now())
            ->each(function (Challenge $challenge) {
                ProgressChallenge::dispatch($challenge);
            });
    }
}
