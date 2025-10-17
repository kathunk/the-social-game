<?php

use App\Challenges\Classes\FarmRound;
use App\Livewire\GameDashboard;
use App\Modifiers\Classes\FarmActions;
use App\Modifiers\Classes\FarmMap;
use App\Modifiers\Classes\FarmSkills;
use App\Modifiers\Classes\FarmTeams;
use Livewire\Livewire;
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

it('allows players to move to adjacent spaces', function () {
    $player = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player->user);

    // Create a team so player spawns on map
    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.FarmTeams::key().'.adjective', 'Happy')
        ->set('round_properties.'.FarmTeams::key().'.noun', 'Farmers')
        ->call('callClassAction', 'createTeam', 'modifier', FarmTeams::key())
        ->assertHasNoErrors();

    // Get player's current position
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $currentSpace = collect($farmMap->modifier_data)
        ->filter(fn ($space) => in_array($player->id, $space['player_ids']))
        ->first();

    // Calculate an adjacent space
    $adjacentX = min($currentSpace['x-index'] + 1, 9);
    $adjacentY = $currentSpace['y-index'];
    $spaceString = chr(65 + $adjacentX).($adjacentY + 1);

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $actionsBefore = $farmActions->modifier_data[$player->id]['actions'];

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.FarmMap::key().'.selected_space', $spaceString)
        ->call('callClassAction', 'move', 'modifier', FarmMap::key())
        ->assertHasNoErrors();

    // Verify player moved to new space
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $spacesWithPlayer = collect($farmMap->modifier_data)
        ->filter(fn ($space) => in_array($player->id, $space['player_ids']));

    // Player should only be in ONE space
    expect($spacesWithPlayer->count())->toBe(1);

    // And it should be the target space
    $newSpace = $spacesWithPlayer->first();
    expect($newSpace['x-index'])->toBe($adjacentX);
    expect($newSpace['y-index'])->toBe($adjacentY);

    // Verify action was consumed
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $actionsAfter = $farmActions->modifier_data[$player->id]['actions'];
    expect($actionsAfter)->toBe($actionsBefore - 1);
});

it('allows Builder level 3 to build roads', function () {
    $player = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player->user);

    // Create team
    \Livewire\Livewire::test(\App\Livewire\GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.FarmTeams::key().'.adjective', 'Happy')
        ->set('round_properties.'.FarmTeams::key().'.noun', 'Farmers')
        ->call('callClassAction', 'createTeam', 'modifier', FarmTeams::key())
        ->assertHasNoErrors();

    $player->refresh(); // Refresh to get team_id

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());

    // Upgrade Builder to level 3
    for ($i = 1; $i <= 3; $i++) {
        \App\Events\PlayerUpgradedSkillInFarm::fire(
            game_id: $this->game->id,
            modifier_id: $farmSkills->id,
            player_id: $player->id,
            skill_name: 'Builder',
            xp_cost: $i,
        );
    }

    Verbs::commit();

    $farmSkills->refresh();
    expect($farmSkills->modifier_data[$player->id]['skills']['Builder'])->toBe(3);

    // Get player's position
    $playerSpace = collect($farmMap->fresh()->modifier_data)
        ->filter(fn ($space) => in_array($player->id, $space['player_ids']))
        ->first();

    // Build a road
    \App\Events\PlayerBuiltRoad::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    );

    Verbs::commit();

    // Verify road was built
    $farmMap = $farmMap->fresh();
    $space = collect($farmMap->modifier_data)
        ->firstWhere(fn ($s) => $s['x-index'] === $playerSpace['x-index'] && $s['y-index'] === $playerSpace['y-index']);

    // Note: PlayerBuiltRoad event currently only sets owner_team_id, not level
    expect($space['road_status']['owner_team_id'])->toBe($player->team_id);

    // Verify action was consumed
    $farmActions = $farmActions->fresh();
    expect($farmActions->modifier_data[$player->id]['actions'])->toBe(2); // Started with 3, built road (-1)
});

it('allows movement along connected road network', function () {
    $player = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player->user);

    // Create team
    \Livewire\Livewire::test(\App\Livewire\GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.FarmTeams::key().'.adjective', 'Happy')
        ->set('round_properties.'.FarmTeams::key().'.noun', 'Farmers')
        ->call('callClassAction', 'createTeam', 'modifier', FarmTeams::key())
        ->assertHasNoErrors();

    $player->refresh(); // Refresh to get team_id

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());

    // Upgrade Builder to level 3
    for ($i = 1; $i <= 3; $i++) {
        \App\Events\PlayerUpgradedSkillInFarm::fire(
            game_id: $this->game->id,
            modifier_id: $farmSkills->id,
            player_id: $player->id,
            skill_name: 'Builder',
            xp_cost: $i,
        );
    }

    Verbs::commit();

    // Get player's starting position
    $playerSpace = collect($farmMap->fresh()->modifier_data)
        ->filter(fn ($space) => in_array($player->id, $space['player_ids']))
        ->first();

    $startX = $playerSpace['x-index'];
    $startY = $playerSpace['y-index'];

    // Count accessible spaces WITHOUT roads
    $farmMapClass = new FarmMap($farmMap->fresh());
    $accessibleWithoutRoads = count($farmMapClass->accessibleSpaces($player, $farmMap->fresh()->modifier_data, $playerSpace));

    // Build a road at player's current position
    \App\Events\PlayerBuiltRoad::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $startX,
        y_index: $startY,
        level: 1,
    );

    Verbs::commit();

    // Build a road adjacent to current position (to the right, if possible)
    $adjacentX = min($startX + 1, 9);
    \App\Events\PlayerBuiltRoad::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $adjacentX,
        y_index: $startY,
        level: 1,
    );

    Verbs::commit();

    // Build a third road further along (two spaces away from start, if possible)
    $farX = min($adjacentX + 1, 9);
    \App\Events\PlayerBuiltRoad::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $farX,
        y_index: $startY,
        level: 1,
    );

    Verbs::commit();

    // Now test that player can move along the road network
    // Player should have MORE accessible spaces with roads than without
    $farmMap = $farmMap->fresh();
    $currentPlayerSpace = collect($farmMap->modifier_data)
        ->filter(fn ($space) => in_array($player->id, $space['player_ids']))
        ->first();

    $farmMapClass = new FarmMap($farmMap);
    $accessibleWithRoads = count($farmMapClass->accessibleSpaces($player, $farmMap->modifier_data, $currentPlayerSpace));

    // With connected roads, should be able to access more spaces
    expect($accessibleWithRoads)->toBeGreaterThan($accessibleWithoutRoads, 'Road network should increase number of accessible spaces');
});
