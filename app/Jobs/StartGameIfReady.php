<?php

namespace App\Jobs;

use App\Events\GameUpdatedForReverb;
use App\Models\Game;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Thunk\Verbs\Facades\Verbs;

class StartGameIfReady implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Game $game) {}

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
