<?php

use App\Challenges\Classes\PyramidScheme;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();
    $this->mockGameTemplate(
        challenges: [
            [
                'challenge_keys' => [PyramidScheme::key()],
                'duration' => 10,
            ],
        ],
        type: 'team',
        team_names: ['Team 1', 'Team 2', 'Team 3', 'Team 4', 'Team 5', 'Team 6', 'Team 7', 'Team 8', 'Team 9', 'Team 10'],
    );
    $this->createGame()->start();
});

it('creates 10 teams at the start of the game', function () {
    expect($this->game->fresh()->teams->count())->toBe(10);
});

it('new players can join a team', function () {
    $team = $this->game->teams->first();
    $player = $this->game->players->first();
    $player->joinTeam($team);

    expect($player->fresh()->team_id)->toBe($team->id);
    expect($team->fresh()->players->count())->toBe(1);
});
