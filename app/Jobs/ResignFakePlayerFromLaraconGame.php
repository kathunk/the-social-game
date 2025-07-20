<?php

namespace App\Jobs;

use App\Models\Modifier;
use App\Models\Player;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ResignFakePlayerFromLaraconGame implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Player $player,
        public Modifier $resignation_modifier,
    ) {
        //
    }

    public function handle(): void
    {
        $points = rand(0, 1) === 0 ? -3 : 3;
        $this->resignation_modifier->handler()->resign($this->player, ['points' => $points]);
    }
}
