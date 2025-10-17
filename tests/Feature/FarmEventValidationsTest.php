<?php

use App\Challenges\Classes\FarmRound;
use App\Events\PlayerAbandonedFarmTeam;
use App\Events\PlayerBootedFromFarmTeam;
use App\Events\PlayerBuiltRoad;
use App\Events\PlayerBuiltSilo;
use App\Events\PlayerDepositedToSilo;
use App\Events\PlayerHarvestedField;
use App\Events\PlayerMovedInFarm;
use App\Events\PlayerPlantedField;
use App\Events\PlayerPromotedToTeamLeaderInFarm;
use App\Events\PlayerRequestedToJoinFarmTeam;
use App\Events\PlayerSeizedFarmProperty;
use App\Events\PlayerUpgradedSilo;
use App\Events\PlayerUpgradedSkillInFarm;
use App\Events\PlayerWithdrewFromSilo;
use App\Events\TeamLeaderAcceptedRequestToJoinFarmTeam;
use App\Events\TeamLeaderDeclinedRequestToJoinFarmTeam;
use App\Livewire\GameDashboard;
use App\Modifiers\Classes\FarmActions;
use App\Modifiers\Classes\FarmMap;
use App\Modifiers\Classes\FarmSkills;
use App\Modifiers\Classes\FarmTeams;
use App\States\PlayerState;
use Livewire\Livewire;
use Thunk\Verbs\Exceptions\EventUnauthorized;
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

// Helper functions
function createTeamForPlayer($player, $game)
{
    Livewire::test(GameDashboard::class, ['game' => $game->fresh()])
        ->set('round_properties.'.FarmTeams::key().'.adjective', 'Happy')
        ->set('round_properties.'.FarmTeams::key().'.noun', 'Farmers')
        ->call('callClassAction', 'createTeam', 'modifier', FarmTeams::key())
        ->assertHasNoErrors();

    return $player->fresh();
}

function getPlayerSpace($game, $player_id)
{
    $farmMap = $game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    return collect($farmMap->modifier_data)
        ->firstWhere(fn ($space) => in_array($player_id, $space['player_ids']));
}

function upgradeSkillToLevel($game, $player, $skill_name, $target_level)
{
    $farmSkills = $game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());

    for ($i = 1; $i <= $target_level; $i++) {
        PlayerUpgradedSkillInFarm::fire(
            game_id: $game->id,
            modifier_id: $farmSkills->id,
            player_id: $player->id,
            skill_name: $skill_name,
            xp_cost: $i,
        );
    }

    Verbs::commit();
}

// Movement Tests
it('allows player to move when they have actions and space is reachable', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    $playerSpace = getPlayerSpace($this->game, $player->id);

    $adjacentX = min($playerSpace['x-index'] + 1, 9);
    $adjacentY = $playerSpace['y-index'];

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());

    PlayerMovedInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmMap->id,
        player_id: $player->id,
        x_index: $adjacentX,
        y_index: $adjacentY,
    );

    Verbs::commit();

    $newPlayerSpace = getPlayerSpace($this->game, $player->id);
    expect($newPlayerSpace['x-index'])->toBe($adjacentX);
    expect($newPlayerSpace['y-index'])->toBe($adjacentY);
});

it('prevents player from moving when they have no actions', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    $playerSpace = getPlayerSpace($this->game, $player->id);

    // Exhaust all actions
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmActions->modifier_data[$player->id]['actions'] = 0;
    $farmActions->updateModelWithStateData();

    $adjacentX = min($playerSpace['x-index'] + 1, 9);
    $adjacentY = $playerSpace['y-index'];

    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());

    expect(fn () => PlayerMovedInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmMap->id,
        player_id: $player->id,
        x_index: $adjacentX,
        y_index: $adjacentY,
    ))->toThrow(EventUnauthorized::class, 'Player does not have enough actions to move');
});

it('prevents player from moving to unreachable space', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    $playerSpace = getPlayerSpace($this->game, $player->id);

    // Try to move to a space far away (not adjacent)
    $farX = min($playerSpace['x-index'] + 5, 9);
    $farY = $playerSpace['y-index'];

    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());

    expect(fn () => PlayerMovedInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmMap->id,
        player_id: $player->id,
        x_index: $farX,
        y_index: $farY,
    ))->toThrow(EventUnauthorized::class, 'Space is not reachable from player\'s current position');
});

// Skill Upgrade Tests
it('allows player to upgrade skill when they have XP and correct cost', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());
    $skillsBefore = $farmSkills->modifier_data[$player->id]['skills']['Builder'];

    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Builder',
        xp_cost: 1,
    );

    Verbs::commit();

    $farmSkills = $farmSkills->fresh();
    expect($farmSkills->modifier_data[$player->id]['skills']['Builder'])->toBe($skillsBefore + 1);
});

it('prevents player from upgrading skill with invalid name', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());

    expect(fn () => PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'InvalidSkill',
        xp_cost: 1,
    ))->toThrow(EventUnauthorized::class, 'Invalid skill name: InvalidSkill');
});

it('prevents player from upgrading skill without enough XP', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());
    $farmSkills->modifier_data[$player->id]['xp'] = 0;
    $farmSkills->updateModelWithStateData();

    expect(fn () => PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->fresh()->id,
        player_id: $player->id,
        skill_name: 'Builder',
        xp_cost: 1,
    ))->toThrow(EventUnauthorized::class, 'Player does not have enough XP');
});

it('prevents player from upgrading skill with incorrect XP cost', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());

    expect(fn () => PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Builder',
        xp_cost: 5, // Should be 1 for level 0->1
    ))->toThrow(EventUnauthorized::class, 'XP cost does not match expected cost for skill level');
});

it('prevents player from upgrading skill beyond max level', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);

    upgradeSkillToLevel($this->game, $player, 'Builder', 3);

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());

    expect(fn () => PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Builder',
        xp_cost: 4,
    ))->toThrow(EventUnauthorized::class, 'Skill is already at maximum level');
});

// Silo Building Tests
it('allows player to build silo when they have actions, in space, and correct builder level', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    upgradeSkillToLevel($this->game, $player, 'Builder', 1);

    $playerSpace = getPlayerSpace($this->game, $player->id);
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    PlayerBuiltSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    );

    Verbs::commit();

    $updatedSpace = getPlayerSpace($this->game, $player->id);
    expect($updatedSpace['silo_status']['level'])->toBe(1);
    expect($updatedSpace['silo_status']['owner_team_id'])->toBe($player->team_id);
});

it('prevents player from building silo without actions', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    upgradeSkillToLevel($this->game, $player, 'Builder', 1);

    $playerSpace = getPlayerSpace($this->game, $player->id);

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmActions->modifier_data[$player->id]['actions'] = 0;
    $farmActions->updateModelWithStateData();

    expect(fn () => PlayerBuiltSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->fresh()->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    ))->toThrow(EventUnauthorized::class, 'Player does not have enough actions to build silo');
});

it('prevents player from building silo when not in correct space', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    upgradeSkillToLevel($this->game, $player, 'Builder', 1);

    $playerSpace = getPlayerSpace($this->game, $player->id);
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    expect(fn () => PlayerBuiltSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'] + 1,
        y_index: $playerSpace['y-index'],
        level: 1,
    ))->toThrow(EventUnauthorized::class, 'Player is not on the specified space');
});

it('prevents player from building silo with incorrect builder level', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);

    $playerSpace = getPlayerSpace($this->game, $player->id);
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    expect(fn () => PlayerBuiltSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    ))->toThrow(EventUnauthorized::class, 'Silo level does not match player\'s Builder skill level');
});

it('prevents player from building silo on invalid terrain', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    upgradeSkillToLevel($this->game, $player, 'Builder', 1);

    $playerSpace = getPlayerSpace($this->game, $player->id);

    // Change terrain to swamp
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($playerSpace) {
        if ($space['x-index'] === $playerSpace['x-index'] && $space['y-index'] === $playerSpace['y-index']) {
            $space['type'] = 'swamp';
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    expect(fn () => PlayerBuiltSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    ))->toThrow(EventUnauthorized::class, 'Cannot build silo on swamp terrain');
});

// Silo Upgrade Tests
it('allows player to upgrade silo when they have actions and correct builder level', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    upgradeSkillToLevel($this->game, $player, 'Builder', 2);

    $playerSpace = getPlayerSpace($this->game, $player->id);
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    // First build a level 1 silo
    PlayerBuiltSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    );

    Verbs::commit();

    // Now upgrade to level 2
    PlayerUpgradedSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->fresh()->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 2,
    );

    Verbs::commit();

    $updatedSpace = getPlayerSpace($this->game, $player->id);
    expect($updatedSpace['silo_status']['level'])->toBe(2);
});

it('prevents player from upgrading silo without actions', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    upgradeSkillToLevel($this->game, $player, 'Builder', 2);

    $playerSpace = getPlayerSpace($this->game, $player->id);
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    // First build a level 1 silo
    PlayerBuiltSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    );

    Verbs::commit();

    // Exhaust actions
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmActions->modifier_data[$player->id]['actions'] = 0;
    $farmActions->updateModelWithStateData();

    expect(fn () => PlayerUpgradedSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->fresh()->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 2,
    ))->toThrow(EventUnauthorized::class, 'Player does not have enough actions to upgrade silo');
});

it('prevents player from upgrading silo owned by other team', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);
    upgradeSkillToLevel($this->game, $player1, 'Builder', 2);

    $this->actingAs($player2->user);
    $player2 = createTeamForPlayer($player2, $this->game);

    $player1Space = getPlayerSpace($this->game, $player1->id);
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    // Player 1 builds a silo
    PlayerBuiltSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player1->id,
        team_id: $player1->team_id,
        x_index: $player1Space['x-index'],
        y_index: $player1Space['y-index'],
        level: 1,
    );

    Verbs::commit();

    // Move player 2 to player 1's space
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($player1Space, $player2) {
        if ($space['x-index'] === $player1Space['x-index'] && $space['y-index'] === $player1Space['y-index']) {
            $space['player_ids'][] = $player2->id;
        } else {
            $space['player_ids'] = array_values(array_diff($space['player_ids'], [$player2->id]));
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    upgradeSkillToLevel($this->game, $player2, 'Builder', 2);

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    expect(fn () => PlayerUpgradedSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player2->id,
        team_id: $player2->team_id,
        x_index: $player1Space['x-index'],
        y_index: $player1Space['y-index'],
        level: 2,
    ))->toThrow(EventUnauthorized::class, 'Silo is not owned by player\'s team');
});

it('prevents player from upgrading silo when already at target level', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    upgradeSkillToLevel($this->game, $player, 'Builder', 2);

    $playerSpace = getPlayerSpace($this->game, $player->id);
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    // Build a level 2 silo directly by manipulating state
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($playerSpace, $player) {
        if ($space['x-index'] === $playerSpace['x-index'] && $space['y-index'] === $playerSpace['y-index']) {
            $space['silo_status']['level'] = 2;
            $space['silo_status']['owner_team_id'] = $player->team_id;
            $space['silo_status']['capacity'] = 20;
            $space['silo_status']['amount'] = 0;
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    expect(fn () => PlayerUpgradedSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->fresh()->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 2,
    ))->toThrow(EventUnauthorized::class, 'Silo is already at or above level 2');
});

// Field Planting Tests
it('allows player to plant field when they have actions and correct farmer level', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    upgradeSkillToLevel($this->game, $player, 'Farmer', 1);

    $playerSpace = getPlayerSpace($this->game, $player->id);
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    PlayerPlantedField::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    );

    Verbs::commit();

    $updatedSpace = getPlayerSpace($this->game, $player->id);
    expect($updatedSpace['field_status']['level'])->toBe(1);
    expect($updatedSpace['field_status']['owner_team_id'])->toBe($player->team_id);
});

it('prevents player from planting field without actions', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    upgradeSkillToLevel($this->game, $player, 'Farmer', 1);

    $playerSpace = getPlayerSpace($this->game, $player->id);

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmActions->modifier_data[$player->id]['actions'] = 0;
    $farmActions->updateModelWithStateData();

    expect(fn () => PlayerPlantedField::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->fresh()->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    ))->toThrow(EventUnauthorized::class, 'Player does not have enough actions to plant field');
});

it('prevents player from planting field with incorrect farmer level', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);

    $playerSpace = getPlayerSpace($this->game, $player->id);
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    expect(fn () => PlayerPlantedField::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    ))->toThrow(EventUnauthorized::class, 'Field level does not match player\'s Farmer skill level');
});

it('prevents player from planting field where field already exists', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    upgradeSkillToLevel($this->game, $player, 'Farmer', 1);

    $playerSpace = getPlayerSpace($this->game, $player->id);
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    // Plant first field
    PlayerPlantedField::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    );

    Verbs::commit();

    expect(fn () => PlayerPlantedField::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->fresh()->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    ))->toThrow(EventUnauthorized::class, 'Space already has a field');
});

it('prevents player from planting field on invalid terrain', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    upgradeSkillToLevel($this->game, $player, 'Farmer', 1);

    $playerSpace = getPlayerSpace($this->game, $player->id);

    // Change terrain to mountain
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($playerSpace) {
        if ($space['x-index'] === $playerSpace['x-index'] && $space['y-index'] === $playerSpace['y-index']) {
            $space['type'] = 'mountain';
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    expect(fn () => PlayerPlantedField::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    ))->toThrow(EventUnauthorized::class, 'Cannot plant field on mountain terrain');
});

// Field Harvesting Tests
it('allows player to harvest field when mature and has capacity', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    $playerSpace = getPlayerSpace($this->game, $player->id);

    // Set up a mature field
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($playerSpace, $player) {
        if ($space['x-index'] === $playerSpace['x-index'] && $space['y-index'] === $playerSpace['y-index']) {
            $space['field_status']['level'] = 1;
            $space['field_status']['owner_team_id'] = $player->team_id;
            $space['field_status']['stage'] = 'mature';
            $space['field_status']['quantity'] = 10;
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    PlayerHarvestedField::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        field_quantity: 10,
        player_capacity: 10,
        player_grain: 0,
    );

    Verbs::commit();

    $farmActions = $farmActions->fresh();
    expect($farmActions->modifier_data[$player->id]['grain'])->toBeGreaterThan(0);
});

it('prevents player from harvesting field without actions', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    $playerSpace = getPlayerSpace($this->game, $player->id);

    // Set up a mature field
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($playerSpace, $player) {
        if ($space['x-index'] === $playerSpace['x-index'] && $space['y-index'] === $playerSpace['y-index']) {
            $space['field_status']['level'] = 1;
            $space['field_status']['owner_team_id'] = $player->team_id;
            $space['field_status']['stage'] = 'mature';
            $space['field_status']['quantity'] = 10;
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmActions->modifier_data[$player->id]['actions'] = 0;
    $farmActions->updateModelWithStateData();

    expect(fn () => PlayerHarvestedField::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->fresh()->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        field_quantity: 10,
        player_capacity: 10,
        player_grain: 0,
    ))->toThrow(EventUnauthorized::class, 'Player does not have enough actions to harvest field');
});

it('prevents player from harvesting immature field', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    $playerSpace = getPlayerSpace($this->game, $player->id);

    // Set up an immature field
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($playerSpace, $player) {
        if ($space['x-index'] === $playerSpace['x-index'] && $space['y-index'] === $playerSpace['y-index']) {
            $space['field_status']['level'] = 1;
            $space['field_status']['owner_team_id'] = $player->team_id;
            $space['field_status']['stage'] = 'seedlings';
            $space['field_status']['quantity'] = 0;
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    expect(fn () => PlayerHarvestedField::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        field_quantity: 0,
        player_capacity: 10,
        player_grain: 0,
    ))->toThrow(EventUnauthorized::class, 'Field is not mature');
});

it('prevents player from harvesting field owned by other team', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    $this->actingAs($player2->user);
    $player2 = createTeamForPlayer($player2, $this->game);

    $player2Space = getPlayerSpace($this->game, $player2->id);

    // Set up a mature field owned by player1's team at player2's location
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($player2Space, $player1) {
        if ($space['x-index'] === $player2Space['x-index'] && $space['y-index'] === $player2Space['y-index']) {
            $space['field_status']['level'] = 1;
            $space['field_status']['owner_team_id'] = $player1->team_id;
            $space['field_status']['stage'] = 'mature';
            $space['field_status']['quantity'] = 10;
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    expect(fn () => PlayerHarvestedField::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player2->id,
        team_id: $player2->team_id,
        x_index: $player2Space['x-index'],
        y_index: $player2Space['y-index'],
        field_quantity: 10,
        player_capacity: 10,
        player_grain: 0,
    ))->toThrow(EventUnauthorized::class, 'Field is not owned by player\'s team');
});

it('prevents player from harvesting field when grain sack is full', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    $playerSpace = getPlayerSpace($this->game, $player->id);

    // Set up a mature field
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($playerSpace, $player) {
        if ($space['x-index'] === $playerSpace['x-index'] && $space['y-index'] === $playerSpace['y-index']) {
            $space['field_status']['level'] = 1;
            $space['field_status']['owner_team_id'] = $player->team_id;
            $space['field_status']['stage'] = 'mature';
            $space['field_status']['quantity'] = 10;
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    expect(fn () => PlayerHarvestedField::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        field_quantity: 10,
        player_capacity: 10,
        player_grain: 10, // Full capacity
    ))->toThrow(EventUnauthorized::class, 'Player\'s grain sack is full');
});

it('prevents player from harvesting field with no grain', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    $playerSpace = getPlayerSpace($this->game, $player->id);

    // Set up a mature field with no grain
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($playerSpace, $player) {
        if ($space['x-index'] === $playerSpace['x-index'] && $space['y-index'] === $playerSpace['y-index']) {
            $space['field_status']['level'] = 1;
            $space['field_status']['owner_team_id'] = $player->team_id;
            $space['field_status']['stage'] = 'mature';
            $space['field_status']['quantity'] = 0;
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    expect(fn () => PlayerHarvestedField::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        field_quantity: 0,
        player_capacity: 10,
        player_grain: 0,
    ))->toThrow(EventUnauthorized::class, 'Field has no grain to harvest');
});

// Silo Deposit Tests
it('allows player to deposit grain to silo when they have grain and silo has capacity', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    $playerSpace = getPlayerSpace($this->game, $player->id);

    // Set up a silo and give player grain
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($playerSpace, $player) {
        if ($space['x-index'] === $playerSpace['x-index'] && $space['y-index'] === $playerSpace['y-index']) {
            $space['silo_status']['level'] = 1;
            $space['silo_status']['owner_team_id'] = $player->team_id;
            $space['silo_status']['capacity'] = 10;
            $space['silo_status']['amount'] = 0;
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmActions->modifier_data[$player->id]['grain'] = 5;
    $farmActions->updateModelWithStateData();

    PlayerDepositedToSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->fresh()->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        amount: 5,
    );

    Verbs::commit();

    $updatedSpace = getPlayerSpace($this->game, $player->id);
    expect($updatedSpace['silo_status']['amount'])->toBe(5);
});

it('prevents player from depositing grain without enough grain', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    $playerSpace = getPlayerSpace($this->game, $player->id);

    // Set up a silo
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($playerSpace, $player) {
        if ($space['x-index'] === $playerSpace['x-index'] && $space['y-index'] === $playerSpace['y-index']) {
            $space['silo_status']['level'] = 1;
            $space['silo_status']['owner_team_id'] = $player->team_id;
            $space['silo_status']['capacity'] = 10;
            $space['silo_status']['amount'] = 0;
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    expect(fn () => PlayerDepositedToSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        amount: 5,
    ))->toThrow(EventUnauthorized::class, 'Player does not have enough grain');
});

it('prevents player from depositing grain when no silo exists', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    $playerSpace = getPlayerSpace($this->game, $player->id);

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmActions->modifier_data[$player->id]['grain'] = 5;
    $farmActions->updateModelWithStateData();

    expect(fn () => PlayerDepositedToSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->fresh()->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        amount: 5,
    ))->toThrow(EventUnauthorized::class, 'There is no silo in this space');
});

it('prevents player from depositing grain to silo owned by other team', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    $this->actingAs($player2->user);
    $player2 = createTeamForPlayer($player2, $this->game);

    $player2Space = getPlayerSpace($this->game, $player2->id);

    // Set up a silo owned by player1's team at player2's location
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($player2Space, $player1) {
        if ($space['x-index'] === $player2Space['x-index'] && $space['y-index'] === $player2Space['y-index']) {
            $space['silo_status']['level'] = 1;
            $space['silo_status']['owner_team_id'] = $player1->team_id;
            $space['silo_status']['capacity'] = 10;
            $space['silo_status']['amount'] = 0;
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmActions->modifier_data[$player2->id]['grain'] = 5;
    $farmActions->updateModelWithStateData();

    expect(fn () => PlayerDepositedToSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->fresh()->id,
        player_id: $player2->id,
        team_id: $player2->team_id,
        x_index: $player2Space['x-index'],
        y_index: $player2Space['y-index'],
        amount: 5,
    ))->toThrow(EventUnauthorized::class, 'Silo is not owned by player\'s team');
});

it('prevents player from depositing grain when silo is full', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    $playerSpace = getPlayerSpace($this->game, $player->id);

    // Set up a full silo
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($playerSpace, $player) {
        if ($space['x-index'] === $playerSpace['x-index'] && $space['y-index'] === $playerSpace['y-index']) {
            $space['silo_status']['level'] = 1;
            $space['silo_status']['owner_team_id'] = $player->team_id;
            $space['silo_status']['capacity'] = 10;
            $space['silo_status']['amount'] = 10;
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmActions->modifier_data[$player->id]['grain'] = 5;
    $farmActions->updateModelWithStateData();

    expect(fn () => PlayerDepositedToSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->fresh()->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        amount: 5,
    ))->toThrow(EventUnauthorized::class, 'Silo does not have enough capacity');
});

// Silo Withdrawal Tests
it('allows player to withdraw grain from silo when they have capacity and silo has grain', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    $playerSpace = getPlayerSpace($this->game, $player->id);

    // Set up a silo with grain
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($playerSpace, $player) {
        if ($space['x-index'] === $playerSpace['x-index'] && $space['y-index'] === $playerSpace['y-index']) {
            $space['silo_status']['level'] = 1;
            $space['silo_status']['owner_team_id'] = $player->team_id;
            $space['silo_status']['capacity'] = 10;
            $space['silo_status']['amount'] = 5;
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    PlayerWithdrewFromSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        amount: 5,
    );

    Verbs::commit();

    $farmActions = $farmActions->fresh();
    expect($farmActions->modifier_data[$player->id]['grain'])->toBe(5);
});

it('prevents player from withdrawing grain without capacity', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    $playerSpace = getPlayerSpace($this->game, $player->id);

    // Set up a silo with grain
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($playerSpace, $player) {
        if ($space['x-index'] === $playerSpace['x-index'] && $space['y-index'] === $playerSpace['y-index']) {
            $space['silo_status']['level'] = 1;
            $space['silo_status']['owner_team_id'] = $player->team_id;
            $space['silo_status']['capacity'] = 10;
            $space['silo_status']['amount'] = 5;
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmActions->modifier_data[$player->id]['grain'] = 10;
    $farmActions->modifier_data[$player->id]['grain_capacity'] = 10;
    $farmActions->updateModelWithStateData();

    expect(fn () => PlayerWithdrewFromSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->fresh()->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        amount: 5,
    ))->toThrow(EventUnauthorized::class, 'Player does not have enough capacity');
});

it('prevents player from withdrawing grain from silo owned by other team', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    $this->actingAs($player2->user);
    $player2 = createTeamForPlayer($player2, $this->game);

    $player2Space = getPlayerSpace($this->game, $player2->id);

    // Set up a silo with grain owned by player1's team at player2's location
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($player2Space, $player1) {
        if ($space['x-index'] === $player2Space['x-index'] && $space['y-index'] === $player2Space['y-index']) {
            $space['silo_status']['level'] = 1;
            $space['silo_status']['owner_team_id'] = $player1->team_id;
            $space['silo_status']['capacity'] = 10;
            $space['silo_status']['amount'] = 5;
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    expect(fn () => PlayerWithdrewFromSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player2->id,
        team_id: $player2->team_id,
        x_index: $player2Space['x-index'],
        y_index: $player2Space['y-index'],
        amount: 5,
    ))->toThrow(EventUnauthorized::class, 'Silo is not owned by player\'s team');
});

it('prevents player from withdrawing more grain than silo has', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    $playerSpace = getPlayerSpace($this->game, $player->id);

    // Set up a silo with limited grain
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($playerSpace, $player) {
        if ($space['x-index'] === $playerSpace['x-index'] && $space['y-index'] === $playerSpace['y-index']) {
            $space['silo_status']['level'] = 1;
            $space['silo_status']['owner_team_id'] = $player->team_id;
            $space['silo_status']['capacity'] = 10;
            $space['silo_status']['amount'] = 2;
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    expect(fn () => PlayerWithdrewFromSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        amount: 5,
    ))->toThrow(EventUnauthorized::class, 'Silo does not have enough grain');
});

// Road Building Tests
it('allows Builder level 3 to build roads', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    upgradeSkillToLevel($this->game, $player, 'Builder', 3);

    $playerSpace = getPlayerSpace($this->game, $player->id);
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    PlayerBuiltRoad::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    );

    Verbs::commit();

    $updatedSpace = getPlayerSpace($this->game, $player->id);
    expect($updatedSpace['road_status']['owner_team_id'])->toBe($player->team_id);
});

it('prevents player from building road without Builder level 3', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);

    $playerSpace = getPlayerSpace($this->game, $player->id);
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    expect(fn () => PlayerBuiltRoad::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    ))->toThrow(EventUnauthorized::class, 'Player must have Builder level 3 to build roads');
});

it('prevents player from building road without actions', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    upgradeSkillToLevel($this->game, $player, 'Builder', 3);

    $playerSpace = getPlayerSpace($this->game, $player->id);

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmActions->modifier_data[$player->id]['actions'] = 0;
    $farmActions->updateModelWithStateData();

    expect(fn () => PlayerBuiltRoad::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->fresh()->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    ))->toThrow(EventUnauthorized::class, 'Player does not have enough actions to build road');
});

it('prevents player from building road where road already exists', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    upgradeSkillToLevel($this->game, $player, 'Builder', 3);

    $playerSpace = getPlayerSpace($this->game, $player->id);
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    // Build first road
    PlayerBuiltRoad::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    );

    Verbs::commit();

    expect(fn () => PlayerBuiltRoad::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->fresh()->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    ))->toThrow(EventUnauthorized::class, 'Space already has a road');
});

it('prevents player from building road on invalid terrain', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);
    upgradeSkillToLevel($this->game, $player, 'Builder', 3);

    $playerSpace = getPlayerSpace($this->game, $player->id);

    // Change terrain to volcano
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($playerSpace) {
        if ($space['x-index'] === $playerSpace['x-index'] && $space['y-index'] === $playerSpace['y-index']) {
            $space['type'] = 'volcano';
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    expect(fn () => PlayerBuiltRoad::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    ))->toThrow(EventUnauthorized::class, 'Cannot build road on volcano terrain');
});

// Property Seizure Tests
it('allows player to seize property with sufficient brute strength', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    $this->actingAs($player2->user);
    $player2 = createTeamForPlayer($player2, $this->game);

    $player2Space = getPlayerSpace($this->game, $player2->id);

    // Set up a field owned by player1's team at player2's location
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($player2Space, $player1) {
        if ($space['x-index'] === $player2Space['x-index'] && $space['y-index'] === $player2Space['y-index']) {
            $space['field_status']['level'] = 1;
            $space['field_status']['owner_team_id'] = $player1->team_id;
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    // Give player2 enough brute strength
    upgradeSkillToLevel($this->game, $player2, 'Brute', 3);

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    PlayerSeizedFarmProperty::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player2->id,
        team_id: $player2->team_id,
        previous_owner_team_id: $player1->team_id,
        x_index: $player2Space['x-index'],
        y_index: $player2Space['y-index'],
        property_type: 'field',
        grain_transferred: 0,
    );

    Verbs::commit();

    $updatedSpace = getPlayerSpace($this->game, $player2->id);
    expect($updatedSpace['field_status']['owner_team_id'])->toBe($player2->team_id);
});

it('prevents player from seizing property without actions', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    $this->actingAs($player2->user);
    $player2 = createTeamForPlayer($player2, $this->game);

    $player2Space = getPlayerSpace($this->game, $player2->id);

    // Set up a field owned by player1's team at player2's location
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($player2Space, $player1) {
        if ($space['x-index'] === $player2Space['x-index'] && $space['y-index'] === $player2Space['y-index']) {
            $space['field_status']['level'] = 1;
            $space['field_status']['owner_team_id'] = $player1->team_id;
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    upgradeSkillToLevel($this->game, $player2, 'Brute', 3);

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmActions->modifier_data[$player2->id]['actions'] = 0;
    $farmActions->updateModelWithStateData();

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    expect(fn () => PlayerSeizedFarmProperty::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player2->id,
        team_id: $player2->team_id,
        previous_owner_team_id: $player1->team_id,
        x_index: $player2Space['x-index'],
        y_index: $player2Space['y-index'],
        property_type: 'field',
        grain_transferred: 0,
    ))->toThrow(EventUnauthorized::class, 'Player does not have enough actions');
});

it('prevents player from seizing property with invalid property type', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    $this->actingAs($player2->user);
    $player2 = createTeamForPlayer($player2, $this->game);

    $player2Space = getPlayerSpace($this->game, $player2->id);

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    expect(fn () => PlayerSeizedFarmProperty::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player2->id,
        team_id: $player2->team_id,
        previous_owner_team_id: $player1->team_id,
        x_index: $player2Space['x-index'],
        y_index: $player2Space['y-index'],
        property_type: 'castle',
        grain_transferred: 0,
    ))->toThrow(EventUnauthorized::class, 'Property type must be either silo or field');
});

it('prevents player from seizing property without sufficient brute strength', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    $this->actingAs($player2->user);
    $player2 = createTeamForPlayer($player2, $this->game);

    $player2Space = getPlayerSpace($this->game, $player2->id);

    // Set up a field owned by player1's team at player2's location
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($player2Space, $player1) {
        if ($space['x-index'] === $player2Space['x-index'] && $space['y-index'] === $player2Space['y-index']) {
            $space['field_status']['level'] = 3;
            $space['field_status']['owner_team_id'] = $player1->team_id;
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    // Player2 has no brute strength upgrades
    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    expect(fn () => PlayerSeizedFarmProperty::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player2->id,
        team_id: $player2->team_id,
        previous_owner_team_id: $player1->team_id,
        x_index: $player2Space['x-index'],
        y_index: $player2Space['y-index'],
        property_type: 'field',
        grain_transferred: 0,
    ))->toThrow(EventUnauthorized::class, 'Not enough brute strength to seize property');
});

// Team Request Tests
it('allows player to request to join team when on same space as leader', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    $this->actingAs($player2->user);
    // Don't create a team for player2 yet

    // Move player2 to player1's space
    $player1Space = getPlayerSpace($this->game, $player1->id);
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($player1Space, $player2) {
        if ($space['x-index'] === $player1Space['x-index'] && $space['y-index'] === $player1Space['y-index']) {
            $space['player_ids'][] = $player2->id;
        } else {
            $space['player_ids'] = array_values(array_diff($space['player_ids'], [$player2->id]));
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    PlayerRequestedToJoinFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player2->id,
        team_id: $player1->team_id,
    );

    Verbs::commit();

    $farmTeams = $farmTeams->fresh();
    expect($farmTeams->modifier_data['requests'][$player2->id])->toBe($player1->team_id);
});

it('prevents player from requesting to join team they are already on', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    expect(fn () => PlayerRequestedToJoinFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player->id,
        team_id: $player->team_id,
    ))->toThrow(EventUnauthorized::class, 'Player is already on this team');
});

it('prevents player from requesting to join team when not on same space as leader', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    $this->actingAs($player2->user);

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    expect(fn () => PlayerRequestedToJoinFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player2->id,
        team_id: $player1->team_id,
    ))->toThrow(EventUnauthorized::class, 'Player is not on the same space as the team leader');
});

// Team Leader Accept Request Tests
it('allows leader to accept request to join team', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    $this->actingAs($player2->user);

    // Move player2 to player1's space and create request
    $player1Space = getPlayerSpace($this->game, $player1->id);
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($player1Space, $player2) {
        if ($space['x-index'] === $player1Space['x-index'] && $space['y-index'] === $player1Space['y-index']) {
            $space['player_ids'][] = $player2->id;
        } else {
            $space['player_ids'] = array_values(array_diff($space['player_ids'], [$player2->id]));
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    PlayerRequestedToJoinFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player2->id,
        team_id: $player1->team_id,
    );

    Verbs::commit();

    TeamLeaderAcceptedRequestToJoinFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->fresh()->id,
        player_id: $player1->id,
        team_id: $player1->team_id,
        requester_id: $player2->id,
    );

    Verbs::commit();

    $farmTeams = $farmTeams->fresh();
    expect($farmTeams->modifier_data['requests'][$player2->id] ?? null)->toBeNull();
});

it('prevents non-leader from accepting request to join team', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $player3 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    // Move player2 and player3 to player1's space
    $player1Space = getPlayerSpace($this->game, $player1->id);
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($player1Space, $player2, $player3) {
        if ($space['x-index'] === $player1Space['x-index'] && $space['y-index'] === $player1Space['y-index']) {
            $space['player_ids'][] = $player2->id;
            $space['player_ids'][] = $player3->id;
        } else {
            $space['player_ids'] = array_values(array_diff($space['player_ids'], [$player2->id, $player3->id]));
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    // Player2 joins team as non-leader
    PlayerRequestedToJoinFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player2->id,
        team_id: $player1->team_id,
    );

    Verbs::commit();

    TeamLeaderAcceptedRequestToJoinFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->fresh()->id,
        player_id: $player1->id,
        team_id: $player1->team_id,
        requester_id: $player2->id,
    );

    Verbs::commit();

    // Update player2's team_id
    $player2State = PlayerState::load($player2->id);
    $player2State->team_id = $player1->team_id;

    // Player3 requests to join
    PlayerRequestedToJoinFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->fresh()->id,
        player_id: $player3->id,
        team_id: $player1->team_id,
    );

    Verbs::commit();

    // Player2 (non-leader) tries to accept player3's request
    expect(fn () => TeamLeaderAcceptedRequestToJoinFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->fresh()->id,
        player_id: $player2->id,
        team_id: $player1->team_id,
        requester_id: $player3->id,
    ))->toThrow(EventUnauthorized::class, 'Player is not the leader of this team');
});

it('prevents leader from accepting non-existent request', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    expect(fn () => TeamLeaderAcceptedRequestToJoinFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player1->id,
        team_id: $player1->team_id,
        requester_id: $player2->id,
    ))->toThrow(EventUnauthorized::class, 'Requester does not have a pending request');
});

// Team Leader Decline Request Tests
it('allows leader to decline request to join team', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    // Move player2 to player1's space and create request
    $player1Space = getPlayerSpace($this->game, $player1->id);
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $farmMap->modifier_data = collect($farmMap->modifier_data)->map(function ($space) use ($player1Space, $player2) {
        if ($space['x-index'] === $player1Space['x-index'] && $space['y-index'] === $player1Space['y-index']) {
            $space['player_ids'][] = $player2->id;
        } else {
            $space['player_ids'] = array_values(array_diff($space['player_ids'], [$player2->id]));
        }
        return $space;
    })->toArray();
    $farmMap->updateModelWithStateData();

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    PlayerRequestedToJoinFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player2->id,
        team_id: $player1->team_id,
    );

    Verbs::commit();

    TeamLeaderDeclinedRequestToJoinFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->fresh()->id,
        player_id: $player1->id,
        team_id: $player1->team_id,
        requester_id: $player2->id,
    );

    Verbs::commit();

    $farmTeams = $farmTeams->fresh();
    expect($farmTeams->modifier_data['requests'][$player2->id] ?? null)->toBeNull();
});

it('prevents non-leader from declining request', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    expect(fn () => TeamLeaderDeclinedRequestToJoinFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player2->id,
        team_id: $player1->team_id,
        requester_id: $player1->id,
    ))->toThrow(EventUnauthorized::class, 'Player is not the leader of this team');
});

it('prevents leader from declining non-existent request', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    expect(fn () => TeamLeaderDeclinedRequestToJoinFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player1->id,
        team_id: $player1->team_id,
        requester_id: $player2->id,
    ))->toThrow(EventUnauthorized::class, 'Requester does not have a pending request to join this team');
});

// Team Leader Promotion Tests
it('allows team member to be promoted to leader', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    // Manually add player2 to team
    $player2State = PlayerState::load($player2->id);
    $player2State->team_id = $player1->team_id;

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    PlayerPromotedToTeamLeaderInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player2->id,
        team_id: $player1->team_id,
    );

    Verbs::commit();

    $farmTeams = $farmTeams->fresh();
    expect($farmTeams->modifier_data['leaders'][$player1->team_id])->toBe($player2->id);
});

it('prevents player not on team from being promoted to leader', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    expect(fn () => PlayerPromotedToTeamLeaderInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player2->id,
        team_id: $player1->team_id,
    ))->toThrow(EventUnauthorized::class, 'Player is not on the specified team');
});

it('prevents player who is already leader from being promoted again', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    expect(fn () => PlayerPromotedToTeamLeaderInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player->id,
        team_id: $player->team_id,
    ))->toThrow(EventUnauthorized::class, 'Player is already the leader of this team');
});

// Team Boot Tests
it('allows leader to boot player from team', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    // Manually add player2 to team
    $player2State = PlayerState::load($player2->id);
    $player2State->team_id = $player1->team_id;

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    PlayerBootedFromFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player2->id,
        team_id: $player1->team_id,
        booter_player_id: $player1->id,
        grain_in_possession: 0,
    );

    Verbs::commit();

    $player2State = PlayerState::load($player2->id);
    expect($player2State->team_id)->toBeNull();
});

it('prevents non-leader from booting player from team', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $player3 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    // Manually add player2 and player3 to team
    $player2State = PlayerState::load($player2->id);
    $player2State->team_id = $player1->team_id;

    $player3State = PlayerState::load($player3->id);
    $player3State->team_id = $player1->team_id;

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    expect(fn () => PlayerBootedFromFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player3->id,
        team_id: $player1->team_id,
        booter_player_id: $player2->id,
        grain_in_possession: 0,
    ))->toThrow(EventUnauthorized::class, 'Booter is not the leader of the team');
});

it('prevents booting player who is not on team', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    expect(fn () => PlayerBootedFromFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player2->id,
        team_id: $player1->team_id,
        booter_player_id: $player1->id,
        grain_in_possession: 0,
    ))->toThrow(EventUnauthorized::class, 'Player is not on the specified team');
});

// Team Abandonment Tests
it('allows player to abandon their team', function () {
    $player = $this->createPlayer();
    $this->game->start();
    $this->actingAs($player->user);

    $player = createTeamForPlayer($player, $this->game);

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    PlayerAbandonedFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player->id,
        team_id: $player->team_id,
        grain_in_possession: 0,
    );

    Verbs::commit();

    $playerState = PlayerState::load($player->id);
    expect($playerState->team_id)->toBeNull();
});

it('prevents player from abandoning team they are not on', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player1->user);
    $player1 = createTeamForPlayer($player1, $this->game);

    $farmTeams = $this->game->fresh()->modifiers->firstWhere('class_key', FarmTeams::key());

    expect(fn () => PlayerAbandonedFarmTeam::fire(
        game_id: $this->game->id,
        modifier_id: $farmTeams->id,
        player_id: $player2->id,
        team_id: $player1->team_id,
        grain_in_possession: 0,
    ))->toThrow(EventUnauthorized::class, 'Player is not on the specified team');
});
