<?php

namespace App\Challenges\Support\Interfaces;

use App\Models\Player;
use App\States\PlayerState;
use Illuminate\Support\Collection;

interface SupportsTeamSwaps
{
    public function swapTeams(Player $player, array $params);

    public function availableTeams(Player $player): Collection;

    public function playerCanSwapTeams(?Player $player = null, ?PlayerState $player_state = null): bool;
}
