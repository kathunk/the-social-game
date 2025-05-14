<?php

namespace App\Console\Commands\Dev;

use App\Models\Game;
use App\Events\GameEnded;
use App\Models\Challenge;
use Thunk\Verbs\Facades\Verbs;
use Illuminate\Console\Command;
use App\Events\Dev\ChallengeForceEnded;
use Illuminate\Support\Facades\Artisan;
use App\Events\Dev\ChallengeForceStarted;

class Next extends Command
{
    protected $signature = 'dev:next';

    protected $description = 'force active challenges to end and next challenges to start';

    public function handle()
    {
        if (config('app.env') !== 'local') {
            $this->error('This command is only available in local environment');

            return;
        }

        Verbs::commitImmediately();

        $active_challenges = Challenge::where('status', 'active')
            ->whereIn('id', function ($query) {
                $query->select('current_challenge_id')
                    ->from('games')
                    ->whereNotNull('current_challenge_id');
            })
            ->get();

        $next_challenges = Challenge::where('status', 'upcoming')
            ->whereIn('starts_at', $active_challenges->pluck('ends_at'))
            ->get();

        $active_challenges
            ->each(function (Challenge $challenge) {
                ChallengeForceEnded::commit(
                    challenge_id: $challenge->id,
                    game_id: $challenge->game_id,
                );
            });

        $next_challenges
            ->each(function (Challenge $challenge) {
                ChallengeForceStarted::commit(
                    challenge_id: $challenge->id,
                    game_id: $challenge->game_id,
                );
            });

        $games_to_end = Game::all()
            ->filter(function (Game $game) {
                return $game->status === 'active' 
                && $game->challenges->where('status', 'active')->isEmpty();
            });

        foreach ($games_to_end as $game) {
            GameEnded::fire(game_id: $game->id);
        }

        Artisan::call('app:progress-games');
    }
}
