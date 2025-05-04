<?php

namespace App\Challenges\Support\Interfaces;

use App\Models\Player;
use App\States\PlayerState;
use Illuminate\Support\Collection;

interface SupportsPeckingOrderBallots
{
    public function vote(Player $player, array $params);

    public function playerCanVote(?Player $player = null, ?PlayerState $player_state = null): bool;
}
