<?php

use App\Challenges\Classes\FarmRound;
use App\Events\PlayerBuiltSilo;
use App\Events\PlayerDepositedToSilo;
use App\Events\PlayerHarvestedField;
use App\Events\PlayerPlantedField;
use App\Events\PlayerUpgradedSilo;
use App\Events\PlayerUpgradedSkillInFarm;
use App\Events\PlayerWithdrewFromSilo;
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

it('allows building silos at different levels based on Builder skill', function () {
    $player = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player->user);

    // Create team
    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.FarmTeams::key().'.adjective', 'Happy')
        ->set('round_properties.'.FarmTeams::key().'.noun', 'Farmers')
        ->call('callClassAction', 'createTeam', 'modifier', FarmTeams::key())
        ->assertHasNoErrors();

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());

    // Upgrade Builder to level 1
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Builder',
        xp_cost: 1,
    );

    Verbs::commit();
    $player->refresh();

    $playerSpace = collect($farmMap->fresh()->modifier_data)
        ->filter(fn ($space) => in_array($player->id, $space['player_ids']))
        ->first();

    // Build level 1 silo
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

    // Verify silo was built
    $farmMap = $farmMap->fresh();
    $space = collect($farmMap->modifier_data)
        ->firstWhere(fn ($s) => $s['x-index'] === $playerSpace['x-index'] && $s['y-index'] === $playerSpace['y-index']);

    expect($space['silo_status']['level'])->toBe(1);
    expect($space['silo_status']['owner_team_id'])->toBe($player->team_id);
    expect($space['silo_status']['capacity'])->toBeGreaterThan(0);
});

it('allows upgrading silos with Builder skill', function () {
    $player = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player->user);

    // Create team
    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.FarmTeams::key().'.adjective', 'Happy')
        ->set('round_properties.'.FarmTeams::key().'.noun', 'Farmers')
        ->call('callClassAction', 'createTeam', 'modifier', FarmTeams::key())
        ->assertHasNoErrors();

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());

    // Upgrade Builder to level 3
    for ($i = 1; $i <= 3; $i++) {
        PlayerUpgradedSkillInFarm::fire(
            game_id: $this->game->id,
            modifier_id: $farmSkills->id,
            player_id: $player->id,
            skill_name: 'Builder',
            xp_cost: $i,
        );
    }

    Verbs::commit();
    $player->refresh();

    $playerSpace = collect($farmMap->fresh()->modifier_data)
        ->filter(fn ($space) => in_array($player->id, $space['player_ids']))
        ->first();

    // Build level 1 silo
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

    // Upgrade to level 2
    PlayerUpgradedSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 2,
    );

    Verbs::commit();

    $farmMap = $farmMap->fresh();
    $space = collect($farmMap->modifier_data)
        ->firstWhere(fn ($s) => $s['x-index'] === $playerSpace['x-index'] && $s['y-index'] === $playerSpace['y-index']);

    expect($space['silo_status']['level'])->toBe(2);

    // Upgrade to level 3
    PlayerUpgradedSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 3,
    );

    Verbs::commit();

    $farmMap = $farmMap->fresh();
    $space = collect($farmMap->modifier_data)
        ->firstWhere(fn ($s) => $s['x-index'] === $playerSpace['x-index'] && $s['y-index'] === $playerSpace['y-index']);

    expect($space['silo_status']['level'])->toBe(3);
});

it('allows depositing and withdrawing grain from silos', function () {
    $player = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player->user);

    // Create team
    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.FarmTeams::key().'.adjective', 'Happy')
        ->set('round_properties.'.FarmTeams::key().'.noun', 'Farmers')
        ->call('callClassAction', 'createTeam', 'modifier', FarmTeams::key())
        ->assertHasNoErrors();

    $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());
    $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());

    // Upgrade Builder and Farmer
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Builder',
        xp_cost: 1,
    );
    PlayerUpgradedSkillInFarm::fire(
        game_id: $this->game->id,
        modifier_id: $farmSkills->id,
        player_id: $player->id,
        skill_name: 'Farmer',
        xp_cost: 1,
    );

    Verbs::commit();
    $player->refresh();

    $playerSpace = collect($farmMap->fresh()->modifier_data)
        ->filter(fn ($space) => in_array($player->id, $space['player_ids']))
        ->first();

    // Build silo
    PlayerBuiltSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        level: 1,
    );

    // Plant and harvest field to get grain
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

    // Advance to mature
    $challenge = $this->game->fresh()->currentChallenge;
    $challenge->end();
    $nextChallenge = $this->game->fresh()->challenges->skip(1)->first();
    $nextChallenge->start();
    $nextChallenge->end();

    // Harvest
    $farmActions = $farmActions->fresh();
    PlayerHarvestedField::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        field_quantity: 5,
        player_capacity: 5,
        player_grain: 0,
    );

    Verbs::commit();

    // Player should have 5 grain
    $farmActions = $farmActions->fresh();
    expect($farmActions->modifier_data[$player->id]['grain'])->toBe(5);

    // Deposit 3 grain to silo
    PlayerDepositedToSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        amount: 3,
    );

    Verbs::commit();

    // Player should have 2 grain, silo should have 3
    $farmActions = $farmActions->fresh();
    expect($farmActions->modifier_data[$player->id]['grain'])->toBe(2);

    $farmMap = $farmMap->fresh();
    $space = collect($farmMap->modifier_data)
        ->firstWhere(fn ($s) => $s['x-index'] === $playerSpace['x-index'] && $s['y-index'] === $playerSpace['y-index']);

    expect($space['silo_status']['amount'])->toBe(3);

    // Withdraw 2 grain from silo
    PlayerWithdrewFromSilo::fire(
        game_id: $this->game->id,
        modifier_id: $farmActions->id,
        player_id: $player->id,
        team_id: $player->team_id,
        x_index: $playerSpace['x-index'],
        y_index: $playerSpace['y-index'],
        amount: 2,
    );

    Verbs::commit();

    // Player should have 4 grain, silo should have 1
    $farmActions = $farmActions->fresh();
    expect($farmActions->modifier_data[$player->id]['grain'])->toBe(4);

    $farmMap = $farmMap->fresh();
    $space = collect($farmMap->modifier_data)
        ->firstWhere(fn ($s) => $s['x-index'] === $playerSpace['x-index'] && $s['y-index'] === $playerSpace['y-index']);

    expect($space['silo_status']['amount'])->toBe(1);
});
