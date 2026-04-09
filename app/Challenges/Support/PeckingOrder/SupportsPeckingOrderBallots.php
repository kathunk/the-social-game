<?php

namespace App\Challenges\Support\PeckingOrder;

use App\Models\Player;
use App\States\PlayerState;

interface SupportsPeckingOrderBallots
{
    public function vote(Player $player, array $params);

    public function playerCanVote(?Player $player = null, ?PlayerState $player_state = null): bool;
}
