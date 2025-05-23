<?php

namespace App\Jobs;

use App\Models\Game;
use Thunk\Verbs\Facades\Verbs;
use App\Events\GameUpdatedForReverb;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class StartGameIfReady implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Game $game)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->game->start();

        Verbs::commit();
        event(new GameUpdatedForReverb($this->game->fresh()));
    }
}
