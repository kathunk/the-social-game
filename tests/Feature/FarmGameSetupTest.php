<?php

use App\Challenges\Classes\FarmRound;
use App\Modifiers\Classes\FarmActions;
use App\Modifiers\Classes\FarmMap;
use App\Modifiers\Classes\FarmSkills;
use App\Modifiers\Classes\FarmTeams;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();

    $challenges = collect(range(1, 5))->map(fn ($i) => [
        'challenge_keys' => [FarmRound::key()],
        'duration' => 10,
    ])->toArray();

    $modifiers = [
        FarmActions::key(),
        FarmSkills::key(),
        FarmTeams::key(),
        FarmMap::key(),
    ];

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'team',
        modifiers: $modifiers,
        team_names: [],
        scoreboard_type: 'team',
    );

    $this->createGame();
});

it('creates farm game with all modifiers', function () {
    $this->game->start();

    expect($this->game->modifiers->count())->toBe(4);
    expect($this->game->modifiers->pluck('class_key')->toArray())->toContain(
        FarmActions::key(),
        FarmSkills::key(),
        FarmTeams::key(),
        FarmMap::key()
    );
});

it('initializes players with default actions and skills on game start', function () {
    $player = $this->createPlayer();
    $this->game->start();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());

    $playerActions = $farmActions->modifier_data[$player->id];
    $playerSkills = $farmSkills->modifier_data[$player->id];

    expect($playerActions['actions'])->toBe(6);
    expect($playerActions['grain'])->toBe(0);
    expect($playerActions['grain_capacity'])->toBe(5);

    expect($playerSkills['xp'])->toBe(3);
    expect($playerSkills['skills'])->toBe([
        'Brute' => 0,
        'Builder' => 0,
        'Chronicler' => 0,
        'Farmer' => 0,
        'Porter' => 0,
        'Scout' => 0,
        'Strategist' => 0,
        'Tactician' => 0,
    ]);
});

it('initializes new players who join after game starts', function () {
    $this->game->start();
    $player = $this->createPlayer();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());

    expect($farmActions->modifier_data)->toHaveKey($player->id);
    expect($farmSkills->modifier_data)->toHaveKey($player->id);

    // Player should NOT be on map until they create a team
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $playerSpaces = collect($farmMap->modifier_data)
        ->filter(fn ($space) => in_array($player->id, $space['player_ids']));
    expect($playerSpaces->count())->toBe(0);
});
