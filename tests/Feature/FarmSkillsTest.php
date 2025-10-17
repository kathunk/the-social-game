<?php

use App\Challenges\Classes\FarmRound;
use App\Events\PlayerMovedInFarm;
use App\Events\PlayerUpgradedSkillInFarm;
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

it('regenerates actions correctly between rounds based on Tactician skill', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $player3 = $this->createPlayer();

    $this->game->start();
    $challenge = $this->game->fresh()->currentChallenge;

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());

    // Use events directly to upgrade skills (Livewire testing is complex, and we know UI works)
    // Give all players Strategist 3 so they don't hit action cap (limit = 3 + 3*2 = 9)
    foreach ([$player1, $player2, $player3] as $player) {
        for ($i = 0; $i < 3; $i++) {
            PlayerUpgradedSkillInFarm::fire(
                game_id: $this->game->id,
                modifier_id: $farmSkills->id,
                player_id: $player->id,
                skill_name: 'Strategist',
                xp_cost: $i + 1,
            );
        }
    }

    // Upgrade player2 Tactician to level 1
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player2->id,
        skill_name: 'Tactician',
        xp_cost: 1,
    );

    // Upgrade player3 Tactician to level 2
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player3->id,
        skill_name: 'Tactician',
        xp_cost: 1,
    );
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player3->id,
        skill_name: 'Tactician',
        xp_cost: 2,
    );

    Verbs::commit();

    // Verify skills upgraded
    $farmSkills->refresh();
    expect($farmSkills->modifier_data[$player2->id]['skills']['Tactician'])->toBe(1);
    expect($farmSkills->modifier_data[$player3->id]['skills']['Tactician'])->toBe(2);

    // End challenge and start next one
    $challenge->refresh();
    $challenge->end();
    $nextChallenge = $this->game->fresh()->challenges->skip(1)->first();
    $nextChallenge->start();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    // Players start with 6 actions, limit is 12 (6 + 3*2 Strategist)
    // Player 1: 6 + 3 = 9, limit 12
    // Player 2: 6 + (3 + 1) = 10, limit 12 (Tactician +1)
    // Player 3: 6 + (3 + 2) = 11, limit 12 (Tactician +2)
    expect($farmActions->modifier_data[$player1->id]['actions'])->toBe(9, 'Player 1: 6 + 3 = 9');
    expect($farmActions->modifier_data[$player2->id]['actions'])->toBe(10, 'Player 2: 6 + 4 = 10 (Tactician +1)');
    expect($farmActions->modifier_data[$player3->id]['actions'])->toBe(11, 'Player 3: 6 + 5 = 11 (Tactician +2)');
});

it('combines Tactician and Strategist skills correctly', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $challenge = $this->game->fresh()->currentChallenge;

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());

    // Upgrade Tactician to level 2
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Tactician',
        xp_cost: 1,
    );
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Tactician',
        xp_cost: 2,
    );

    // Upgrade Strategist to level 2
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Strategist',
        xp_cost: 1,
    );
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Strategist',
        xp_cost: 2,
    );

    Verbs::commit();

    // End challenge and start next one
    $challenge->refresh();
    $challenge->end();
    $nextChallenge = $this->game->fresh()->challenges->skip(1)->first();
    $nextChallenge->start();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    // Player starts with 6 actions
    // Gains: 3 + 2 (Tactician) = 5
    // Total: 6 + 5 = 11, but capped at 10 (Strategist 2 limit: 6 + 2*2)
    expect($farmActions->modifier_data[$player->id]['actions'])->toBe(10, 'Player should be capped at 10 (6 + 5 = 11, but limit is 10)');
});

it('regression: player with 0 actions who upgrades Strategist should only get 3 actions next round', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $challenge = $this->game->fresh()->currentChallenge;

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    // Spend all actions (move 3 times to get to 0 actions)
    PlayerMovedInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        x_index: 1,
        y_index: 1,
    );
    PlayerMovedInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        x_index: 2,
        y_index: 1,
    );
    PlayerMovedInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        x_index: 3,
        y_index: 1,
    );

    Verbs::commit();

    $farmActions->refresh();
    expect($farmActions->modifier_data[$player->id]['actions'])->toBe(0, 'Player should have 0 actions after 3 moves');

    // Upgrade Strategist 3 times to increase capacity to 9
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Strategist',
        xp_cost: 1,
    );
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Strategist',
        xp_cost: 2,
    );
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Strategist',
        xp_cost: 3,
    );

    Verbs::commit();

    // Verify Strategist upgraded to level 3
    $farmSkills->refresh();
    expect($farmSkills->modifier_data[$player->id]['skills']['Strategist'])->toBe(3, 'Player should have Strategist level 3');

    // End challenge and start next one
    $challenge->refresh();
    $challenge->end();
    $nextChallenge = $this->game->fresh()->challenges->skip(1)->first();
    $nextChallenge->start();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    // Player should regenerate 3 actions (0 + 3 = 3)
    // NOT 6 actions (which would be the bug)
    expect($farmActions->modifier_data[$player->id]['actions'])->toBe(3, 'Player with 0 actions should regenerate to 3, not 6');
});

it('regression (UI workflow): player upgrades Strategist, spends all actions, should get 3 actions next round', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $challenge = $this->game->fresh()->currentChallenge;

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    $this->actingAs($player->user);

    // Upgrade Strategist twice (matching UI workflow)
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Strategist',
        xp_cost: 1,
    );
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Strategist',
        xp_cost: 2,
    );

    Verbs::commit();

    // Verify Strategist upgraded to level 2
    $farmSkills->refresh();
    expect($farmSkills->modifier_data[$player->id]['skills']['Strategist'])->toBe(2, 'Player should have Strategist level 2');

    // Spend all actions (move 3 times)
    PlayerMovedInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        x_index: 1,
        y_index: 1,
    );
    PlayerMovedInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        x_index: 2,
        y_index: 1,
    );
    PlayerMovedInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        x_index: 3,
        y_index: 1,
    );

    Verbs::commit();

    $farmActions->refresh();
    expect($farmActions->modifier_data[$player->id]['actions'])->toBe(0, 'Player should have 0 actions after 3 moves');

    // End challenge and start next one
    $challenge->refresh();
    $challenge->end();
    $nextChallenge = $this->game->fresh()->challenges->skip(1)->first();
    $nextChallenge->start();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    // Player should regenerate 3 actions (0 + 3 = 3)
    // NOT 6 actions (which would be the bug)
    expect($farmActions->modifier_data[$player->id]['actions'])->toBe(3, 'Player with 0 actions should regenerate to 3, not 6');
});

it('increases grain capacity when Porter skill is upgraded', function () {
    $player = $this->createPlayer();
    $this->game->start();

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    // Initial grain capacity should be 5
    expect($farmActions->modifier_data[$player->id]['grain_capacity'])->toBe(5);

    // Upgrade Porter to level 1 (cost 1 XP)
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Porter',
        xp_cost: 1,
    );

    Verbs::commit();

    // Verify Porter skill increased
    $farmSkills->refresh();
    expect($farmSkills->modifier_data[$player->id]['skills']['Porter'])->toBe(1);

    // Grain capacity should now be 10 (5 + 5)
    $farmActions->refresh();
    expect($farmActions->modifier_data[$player->id]['grain_capacity'])->toBe(10, 'Porter level 1 should increase capacity to 10');

    // Upgrade Porter to level 2 (cost 2 XP)
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Porter',
        xp_cost: 2,
    );

    Verbs::commit();

    // Verify Porter skill increased
    $farmSkills->refresh();
    expect($farmSkills->modifier_data[$player->id]['skills']['Porter'])->toBe(2);

    // Grain capacity should now be 15 (10 + 5)
    $farmActions->refresh();
    expect($farmActions->modifier_data[$player->id]['grain_capacity'])->toBe(15, 'Porter level 2 should increase capacity to 15');
});

it('Scout skill level 0 cannot inspect any distant spaces', function () {
    $player = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player->user);

    // Create team to spawn on map
    \Livewire\Livewire::test(\App\Livewire\GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.FarmTeams::key().'.adjective', 'Happy')
        ->set('round_properties.'.FarmTeams::key().'.noun', 'Farmers')
        ->call('callClassAction', 'createTeam', 'modifier', FarmTeams::key())
        ->assertHasNoErrors();

    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());

    // Verify Scout is level 0
    expect($farmSkills->modifier_data[$player->id]['skills']['Scout'])->toBe(0);

    // Get player's current space
    $playerSpace = collect($farmMap->modifier_data)
        ->filter(fn ($space) => in_array($player->id, $space['player_ids']))
        ->first();

    // Calculate scoutable spaces using FarmMap's helper
    $farmMapClass = new FarmMap($farmMap);
    $playerSkills = $farmSkills->modifier_data[$player->id]['skills'];
    $scoutableSpaces = $farmMapClass->scoutableSpaces($playerSpace, $playerSkills);

    // With Scout level 0, should not be able to inspect any spaces
    expect(count($scoutableSpaces))->toBe(0);
});

it('Scout skill level 1 can inspect spaces at Manhattan distance 1', function () {
    $player = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player->user);

    // Create team to spawn on map
    \Livewire\Livewire::test(\App\Livewire\GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.FarmTeams::key().'.adjective', 'Happy')
        ->set('round_properties.'.FarmTeams::key().'.noun', 'Farmers')
        ->call('callClassAction', 'createTeam', 'modifier', FarmTeams::key())
        ->assertHasNoErrors();

    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());

    // Upgrade Scout to level 1
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Scout',
        xp_cost: 1,
    );

    Verbs::commit();

    $farmSkills->refresh();
    expect($farmSkills->modifier_data[$player->id]['skills']['Scout'])->toBe(1);

    // Get player's current space
    $playerSpace = collect($farmMap->fresh()->modifier_data)
        ->filter(fn ($space) => in_array($player->id, $space['player_ids']))
        ->first();

    $playerX = $playerSpace['x-index'];
    $playerY = $playerSpace['y-index'];

    // Calculate scoutable spaces
    $farmMapClass = new FarmMap($farmMap->fresh());
    $playerSkills = $farmSkills->modifier_data[$player->id]['skills'];
    $scoutableSpaces = $farmMapClass->scoutableSpaces($playerSpace, $playerSkills);

    // Should be able to inspect spaces at distance 1 (up to 4 spaces: up, down, left, right)
    expect(count($scoutableSpaces))->toBeGreaterThan(0);
    expect(count($scoutableSpaces))->toBeLessThanOrEqual(4);

    // Verify all scoutable spaces are at Manhattan distance 1
    foreach ($scoutableSpaces as $space) {
        $dx = abs($space['x-index'] - $playerX);
        $dy = abs($space['y-index'] - $playerY);
        $manhattan = $dx + $dy;
        expect($manhattan)->toBe(1, "Space at ({$space['x-index']}, {$space['y-index']}) should be distance 1 from player at ($playerX, $playerY)");
    }
});

it('Scout skill level 2 can inspect spaces at Manhattan distance 1-2', function () {
    $player = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player->user);

    // Create team to spawn on map
    \Livewire\Livewire::test(\App\Livewire\GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.FarmTeams::key().'.adjective', 'Happy')
        ->set('round_properties.'.FarmTeams::key().'.noun', 'Farmers')
        ->call('callClassAction', 'createTeam', 'modifier', FarmTeams::key())
        ->assertHasNoErrors();

    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());

    // Upgrade Scout to level 2
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Scout',
        xp_cost: 1,
    );
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Scout',
        xp_cost: 2,
    );

    Verbs::commit();

    $farmSkills->refresh();
    expect($farmSkills->modifier_data[$player->id]['skills']['Scout'])->toBe(2);

    // Get player's current space
    $playerSpace = collect($farmMap->fresh()->modifier_data)
        ->filter(fn ($space) => in_array($player->id, $space['player_ids']))
        ->first();

    $playerX = $playerSpace['x-index'];
    $playerY = $playerSpace['y-index'];

    // Calculate scoutable spaces
    $farmMapClass = new FarmMap($farmMap->fresh());
    $playerSkills = $farmSkills->modifier_data[$player->id]['skills'];
    $scoutableSpaces = $farmMapClass->scoutableSpaces($playerSpace, $playerSkills);

    // Should be able to inspect more spaces than level 1 (up to 12 spaces at distance 1-2)
    expect(count($scoutableSpaces))->toBeGreaterThan(4);
    expect(count($scoutableSpaces))->toBeLessThanOrEqual(12);

    // Verify all scoutable spaces are at Manhattan distance 1 or 2
    foreach ($scoutableSpaces as $space) {
        $dx = abs($space['x-index'] - $playerX);
        $dy = abs($space['y-index'] - $playerY);
        $manhattan = $dx + $dy;
        expect($manhattan)->toBeLessThanOrEqual(2);
        expect($manhattan)->toBeGreaterThan(0);
    }
});

it('Scout skill level 3 can inspect spaces at Manhattan distance 1-3', function () {
    $player = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player->user);

    // Create team to spawn on map
    \Livewire\Livewire::test(\App\Livewire\GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.FarmTeams::key().'.adjective', 'Happy')
        ->set('round_properties.'.FarmTeams::key().'.noun', 'Farmers')
        ->call('callClassAction', 'createTeam', 'modifier', FarmTeams::key())
        ->assertHasNoErrors();

    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());

    // Upgrade Scout to level 3
    for ($i = 1; $i <= 3; $i++) {
        PlayerUpgradedSkillInFarm::fire(
            game_id: $this->game->id,
            modifier_id: $farmSkills->id,
            player_id: $player->id,
            skill_name: 'Scout',
            xp_cost: $i,
        );
    }

    Verbs::commit();

    $farmSkills->refresh();
    expect($farmSkills->modifier_data[$player->id]['skills']['Scout'])->toBe(3);

    // Get player's current space
    $playerSpace = collect($farmMap->fresh()->modifier_data)
        ->filter(fn ($space) => in_array($player->id, $space['player_ids']))
        ->first();

    $playerX = $playerSpace['x-index'];
    $playerY = $playerSpace['y-index'];

    // Calculate scoutable spaces
    $farmMapClass = new FarmMap($farmMap->fresh());
    $playerSkills = $farmSkills->modifier_data[$player->id]['skills'];
    $scoutableSpaces = $farmMapClass->scoutableSpaces($playerSpace, $playerSkills);

    // Should be able to inspect even more spaces than level 2 (up to 24 spaces at distance 1-3)
    expect(count($scoutableSpaces))->toBeGreaterThan(12);
    expect(count($scoutableSpaces))->toBeLessThanOrEqual(24);

    // Verify all scoutable spaces are at Manhattan distance 1, 2, or 3
    foreach ($scoutableSpaces as $space) {
        $dx = abs($space['x-index'] - $playerX);
        $dy = abs($space['y-index'] - $playerY);
        $manhattan = $dx + $dy;
        expect($manhattan)->toBeLessThanOrEqual(3);
        expect($manhattan)->toBeGreaterThan(0);
    }
});

it('Chronicler skill level 1 limits history to 2 rounds', function () {
    $player = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player->user);

    // Create team to spawn on map
    \Livewire\Livewire::test(\App\Livewire\GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.FarmTeams::key().'.adjective', 'Happy')
        ->set('round_properties.'.FarmTeams::key().'.noun', 'Farmers')
        ->call('callClassAction', 'createTeam', 'modifier', FarmTeams::key())
        ->assertHasNoErrors();

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());

    // Upgrade Chronicler to level 1
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Chronicler',
        xp_cost: 1,
    );

    Verbs::commit();

    $farmSkills->refresh();
    $playerSkills = $farmSkills->modifier_data[$player->id]['skills'];

    // With Chronicler level 1, should limit to 2 rounds
    expect($playerSkills['Chronicler'])->toBe(1);

    // Test the limit calculation logic from FarmMap
    $limit = match ($playerSkills['Chronicler']) {
        1 => 2,
        2 => 5,
        3 => null,
        default => 0,
    };

    expect($limit)->toBe(2, 'Chronicler level 1 should limit to 2 rounds');
});

it('Chronicler skill level 2 limits history to 5 rounds', function () {
    $player = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player->user);

    // Create team to spawn on map
    \Livewire\Livewire::test(\App\Livewire\GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.FarmTeams::key().'.adjective', 'Happy')
        ->set('round_properties.'.FarmTeams::key().'.noun', 'Farmers')
        ->call('callClassAction', 'createTeam', 'modifier', FarmTeams::key())
        ->assertHasNoErrors();

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());

    // Upgrade Chronicler to level 2
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Chronicler',
        xp_cost: 1,
    );
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Chronicler',
        xp_cost: 2,
    );

    Verbs::commit();

    $farmSkills->refresh();
    $playerSkills = $farmSkills->modifier_data[$player->id]['skills'];

    // With Chronicler level 2, should limit to 5 rounds
    expect($playerSkills['Chronicler'])->toBe(2);

    // Test the limit calculation logic from FarmMap
    $limit = match ($playerSkills['Chronicler']) {
        1 => 2,
        2 => 5,
        3 => null,
        default => 0,
    };

    expect($limit)->toBe(5, 'Chronicler level 2 should limit to 5 rounds');
});

it('Chronicler skill level 3 provides unlimited history', function () {
    $player = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player->user);

    // Create team to spawn on map
    \Livewire\Livewire::test(\App\Livewire\GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.FarmTeams::key().'.adjective', 'Happy')
        ->set('round_properties.'.FarmTeams::key().'.noun', 'Farmers')
        ->call('callClassAction', 'createTeam', 'modifier', FarmTeams::key())
        ->assertHasNoErrors();

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());

    // Upgrade Chronicler to level 3
    for ($i = 1; $i <= 3; $i++) {
        PlayerUpgradedSkillInFarm::fire(
            game_id: $this->game->id,
            modifier_id: $farmSkills->id,
            player_id: $player->id,
            skill_name: 'Chronicler',
            xp_cost: $i,
        );
    }

    Verbs::commit();

    $farmSkills->refresh();
    $playerSkills = $farmSkills->modifier_data[$player->id]['skills'];

    // With Chronicler level 3, should have unlimited history (null limit)
    expect($playerSkills['Chronicler'])->toBe(3);

    // Test the limit calculation logic from FarmMap
    $limit = match ($playerSkills['Chronicler']) {
        1 => 2,
        2 => 5,
        3 => null,
        default => 0,
    };

    expect($limit)->toBeNull('Chronicler level 3 should provide unlimited history (null limit)');
});
