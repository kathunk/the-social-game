<?php

namespace App\Jobs;

use App\Models\Challenge;
use Thunk\Verbs\Facades\Verbs;
use App\Events\GameUpdatedForReverb;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProgressChallenge implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Challenge $challenge)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->challenge->end();
        Verbs::commit();

        $next_challenge = $this->challenge->game->challenges->firstWhere('status', 'upcoming');

        if ($next_challenge) {
            $next_challenge->start();
            Verbs::commit();
        }

        if (!$next_challenge) {
            $this->challenge->game->end();
            Verbs::commit();
        }

        event(new GameUpdatedForReverb($this->challenge->game->fresh()));
    }
}
