<?php

namespace App\Challenges\Support\Traits;

use App\Challenges\Support\Interfaces\SupportsTeamSwaps;
use App\Events\PlayerJoinedTeam;
use App\Models\Player;
use Thunk\Verbs\Facades\Verbs;

trait HasTeamSwaps
{
    public function swapTeams(Player $player, array $params)
    {
        if (! $this instanceof SupportsTeamSwaps) {
            throw new \RuntimeException('Challenge class must implement SupportsTeamSwaps interface');
        }

        PlayerJoinedTeam::fire(
            player_id: $player->id,
            team_id: (int) $params['team_id'],
            game_id: $player->game_id,
            previous_team_id: $player->team_id,
        );

        Verbs::commit();
    }
}
