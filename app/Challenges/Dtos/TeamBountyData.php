<?php

namespace App\Challenges\Dtos;

use App\States\GameState;
use App\States\ChallengeState;
use Illuminate\Support\Collection;

class TeamBountyData extends ChallengeData
{
    public function __construct(
        public array $team_bounties = [],
        public array $swapper_ids = []
    ) {}

    public static function fromGameAndChallenge(GameState $game, ChallengeState $challenge): static
    {
        $teams = $game->teams();

        // Step 1: Create a pool of potential bounty targets from each team
        $teamBountyPools = [];
        foreach ($teams as $team) {
            $teamBountyPools[$team->id] = $team->player_ids->shuffle()->take(3)->values()->all();
        }

        // Step 2: Assign bounties to each team (3 players from other teams)
        $teamBounties = [];

        foreach ($teams as $team) {
            $teamId = $team->id;
            $teamBounties[$teamId] = [];

            // Get eligible teams (all teams except the current team)
            $otherTeamIds = $teams->pluck('id')->reject(fn($id) => $id === $teamId)->values();

            // Shuffle and take 3 different teams if possible
            $selectedTeamIds = $otherTeamIds->shuffle()->take(min(3, $otherTeamIds->count()))->values();

            // For each selected team, pick one random player as a bounty
            foreach ($selectedTeamIds as $otherTeamId) {
                if (!empty($teamBountyPools[$otherTeamId])) {
                    $randomPlayerIndex = array_rand($teamBountyPools[$otherTeamId]);
                    $randomPlayerId = $teamBountyPools[$otherTeamId][$randomPlayerIndex];
                    $teamBounties[$teamId][] = $randomPlayerId;
                }
            }
        }

        return new static(
            team_bounties: $teamBounties,
            swapper_ids: []
        );
    }
}
