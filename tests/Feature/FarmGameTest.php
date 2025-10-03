<?php

use App\Challenges\Classes\FarmRound;
use App\Events\PlayerMovedInFarm;
use App\Events\PlayerUpgradedSkillInFarm;
use App\Livewire\GameDashboard;
use App\Livewire\SecretsPage;
use App\Modifiers\Classes\FarmActions;
use App\Modifiers\Classes\FarmMap;
use App\Modifiers\Classes\FarmSkills;
use App\Modifiers\Classes\FarmTeams;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();

    $challenges = collect(range(1, 3))->map(fn ($i) => [
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

    expect($playerActions['actions'])->toBe(3);
    expect($playerActions['grain'])->toBe(0);
    expect($playerActions['grain_capacity'])->toBe(5);

    expect($playerSkills['xp'])->toBe(20);
    expect($playerSkills['skills'])->toBe([
        'Brute' => 0,
        'Builder' => 0,
        'Farmer' => 0,
        'Porter' => 0,
        'Strategist' => 0,
        'Tactician' => 0,
        'Thief' => 0,
    ]);
});

it('places players in random spaces on the map on game start', function () {
    $player = $this->createPlayer();
    $this->game->start();

    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());

    $playerSpaces = collect($farmMap->modifier_data)
        ->filter(fn ($space) => in_array($player->id, $space['player_ids']));

    expect($playerSpaces->count())->toBe(1);

    $space = $playerSpaces->first();
    expect($space)->toHaveKeys(['x-index', 'y-index', 'type', 'player_ids', 'farm_status', 'road_status', 'silo_status']);
});

it('initializes new players who join after game starts', function () {
    $this->game->start();
    $player = $this->createPlayer();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());

    expect($farmActions->modifier_data)->toHaveKey($player->id);
    expect($farmSkills->modifier_data)->toHaveKey($player->id);

    $playerSpaces = collect($farmMap->modifier_data)
        ->filter(fn ($space) => in_array($player->id, $space['player_ids']));
    expect($playerSpaces->count())->toBe(1);
});

it('allows players to create and join teams', function () {
    $player = $this->createPlayer();
    $this->game->start();

    expect($player->fresh()->team)->toBeNull();

    $this->actingAs($player->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.FarmTeams::key().'.adjective', 'Happy')
        ->set('round_properties.'.FarmTeams::key().'.noun', 'Farmers')
        ->call('callClassAction', 'createTeam', 'modifier', FarmTeams::key())
        ->assertHasNoErrors();

    $player->refresh();
    expect($player->team)->not->toBeNull();
    expect($player->team->name)->toBe('Happy Farmers');
});

it('allows players to move to adjacent spaces', function () {
    $player = $this->createPlayer();
    $this->game->start();

    // Get player's current position
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $currentSpace = collect($farmMap->modifier_data)
        ->filter(fn ($space) => in_array($player->id, $space['player_ids']))
        ->first();

    // Calculate an adjacent space
    $adjacentX = min($currentSpace['x-index'] + 1, 9);
    $adjacentY = $currentSpace['y-index'];
    $spaceString = chr(65 + $adjacentX) . ($adjacentY + 1);

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $actionsBefore = $farmActions->modifier_data[$player->id]['actions'];

    $this->actingAs($player->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.FarmActions::key().'.space_string', $spaceString)
        ->call('callClassAction', 'move', 'modifier', FarmActions::key())
        ->assertHasNoErrors();

    // Verify player moved
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
    $newSpace = collect($farmMap->modifier_data)
        ->filter(fn ($space) => in_array($player->id, $space['player_ids']))
        ->first();

    expect($newSpace['x-index'])->toBe($adjacentX);
    expect($newSpace['y-index'])->toBe($adjacentY);

    // Verify old space doesn't have player
    $oldSpace = collect($farmMap->modifier_data)
        ->firstWhere(function ($space) use ($currentSpace) {
            return $space['x-index'] === $currentSpace['x-index']
                && $space['y-index'] === $currentSpace['y-index'];
        });
    expect($oldSpace['player_ids'])->not->toContain($player->id);

    // Verify action was consumed
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $actionsAfter = $farmActions->modifier_data[$player->id]['actions'];
    expect($actionsAfter)->toBe($actionsBefore - 1);
});

it('allows players to upgrade skills via the secrets page', function () {
    $player = $this->createPlayer();
    $this->game->start();

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());

    $xpBefore = $farmSkills->modifier_data[$player->id]['xp'];
    $tacticianBefore = $farmSkills->modifier_data[$player->id]['skills']['Tactician'];

    $this->actingAs($player->user);

    Livewire::test(SecretsPage::class, [
        'game' => $this->game->fresh(),
        'modifier' => $farmSkills->fresh(),
    ])
        ->set('round_properties.'.FarmSkills::key().'.selected_skill_to_upgrade', 'Tactician')
        ->call('callClassAction', 'upgradeSkill', 'modifier', FarmSkills::key())
        ->assertHasNoErrors();

    $farmSkills->refresh();
    $xpAfter = $farmSkills->modifier_data[$player->id]['xp'];
    $tacticianAfter = $farmSkills->modifier_data[$player->id]['skills']['Tactician'];

    expect($tacticianAfter)->toBe($tacticianBefore + 1);
    expect($xpAfter)->toBe($xpBefore - 1); // Cost is level + 1, so 0 + 1 = 1
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

    // Players start with 3 actions
    // Player 1: 3 + (3 + 0) = 6, limit 9
    // Player 2: 3 + (3 + 1) = 7, limit 9
    // Player 3: 3 + (3 + 2) = 8, limit 9
    expect($farmActions->modifier_data[$player1->id]['actions'])->toBe(6, 'Player 1: 3 + 3 = 6');
    expect($farmActions->modifier_data[$player2->id]['actions'])->toBe(7, 'Player 2: 3 + 4 = 7 (Tactician +1)');
    expect($farmActions->modifier_data[$player3->id]['actions'])->toBe(8, 'Player 3: 3 + 5 = 8 (Tactician +2)');
});

it('respects action limit based on Strategist skill', function () {
    $player1 = $this->createPlayer();
    $player2 = $this->createPlayer();
    $player3 = $this->createPlayer();

    $this->game->start();
    $challenge = $this->game->fresh()->currentChallenge;

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());

    // Player 2: Strategist level 1 (limit 5 actions)
    $this->actingAs($player2->user);
    Livewire::test(SecretsPage::class, [
        'game' => $this->game->fresh(),
        'modifier' => $farmSkills->fresh(),
    ])
        ->set('round_properties.'.FarmSkills::key().'.selected_skill_to_upgrade', 'Strategist')
        ->call('callClassAction', 'upgradeSkill', 'modifier', FarmSkills::key());

    // Player 3: Strategist level 2 (limit 7 actions)
    $this->actingAs($player3->user);
    Livewire::test(SecretsPage::class, [
        'game' => $this->game->fresh(),
        'modifier' => $farmSkills->fresh(),
    ])
        ->set('round_properties.'.FarmSkills::key().'.selected_skill_to_upgrade', 'Strategist')
        ->call('callClassAction', 'upgradeSkill', 'modifier', FarmSkills::key())
        ->set('round_properties.'.FarmSkills::key().'.selected_skill_to_upgrade', 'Strategist')
        ->call('callClassAction', 'upgradeSkill', 'modifier', FarmSkills::key());

    // End challenge and start next one
    $challenge->refresh();
    $challenge->end();
    $nextChallenge = $this->game->fresh()->challenges->skip(1)->first();
    $nextChallenge->start();

    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());

    // All players start with 3 actions and gain 3 more (no Tactician) = 6 total
    // Player 1: min(6, 3) = 3 (capped)
    // Player 2: min(6, 5) = 5 (capped)
    // Player 3: min(6, 7) = 6 (within limit)
    expect($farmActions->modifier_data[$player1->id]['actions'])->toBe(3, 'Player 1 capped at 3 (limit 3)');
    expect($farmActions->modifier_data[$player2->id]['actions'])->toBe(5, 'Player 2 capped at 5 (limit 5)');
    expect($farmActions->modifier_data[$player3->id]['actions'])->toBe(6, 'Player 3 has 6 (limit 7)');
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

    // Player starts with 3 actions
    // Gains: 3 + 2 (Tactician) = 5
    // Total: 3 + 5 = 8, but capped at 7 (Strategist 2 limit)
    expect($farmActions->modifier_data[$player->id]['actions'])->toBe(7, 'Player should be capped at 7 (3 + 5 = 8, but limit is 7)');
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
