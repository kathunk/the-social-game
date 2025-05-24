<?php

namespace App\Challenges\Support\Traits;

use Illuminate\Support\Collection;

trait HasTeamPairs
{
    public function pair(Collection $teams): Collection
    {
        // @todo in the future: account for scenarios where there is an odd number of teams

        $paired_teams = collect();

        $teams = $teams->keys();

        while ($teams->count() >= 2) {
            [$team1, $team2] = $teams->splice(0, 2);

            $paired_teams
                ->put($team1, $team2)
                ->put($team2, $team1);
        }

        return $paired_teams;
    }
}
