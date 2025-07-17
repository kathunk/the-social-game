<?php

namespace App\Console\Commands\Dev;

use App\Events\ChallengeEnded;
use App\Events\GameEnded;
use App\Models\Game;
use Illuminate\Console\Command;
use Thunk\Verbs\Facades\Verbs;

class NextChallenge extends Command
{
    protected $signature = 'dev:next {game_id}';

    protected $description = 'force active challenges to end and next challenges to start';

    public function handle()
    {
        if (config('app.env') !== 'local') {
            $this->error('This command is only available in local environment');

            return;
        }

        $game = Game::find($this->argument('game_id'));

        Verbs::commitImmediately();

        $active_challenge = $game->fresh()->challenges->where('status', 'active')->first();

        if ($active_challenge !== null) {
            ChallengeEnded::fire(
                challenge_id: $active_challenge->id,
                game_id: $active_challenge->game_id,
            );
        }

        $next_challenge = $game->fresh()->challenges
            ->where('status', 'upcoming')
            ->sortBy('starts_at')
            ->first();

        if ($next_challenge !== null) {
            $next_challenge->start();

            return;
        }

        GameEnded::fire(game_id: $game->id);
    }
}
