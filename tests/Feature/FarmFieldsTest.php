<?php

use App\Challenges\Classes\FarmRound;
use App\Events\PlayerHarvestedField;
use App\Events\PlayerPlantedField;
use App\Events\PlayerUpgradedSkillInFarm;
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

// TODO: Add field lifecycle test (seedlings -> sprouts -> mature -> rotted)
// Working version exists in FarmGameTest.php but can be flaky due to random volcano

// TODO: These harvest tests are flaky because:
// 1. Players spawn on random map spaces
// 2. Those spaces might be fertile_ashlands (2x grain production)
// 3. Those spaces might be adjacent to volcano (field gets destroyed)
// Need to create a controlled test environment with fixed map layout

// it('allows players to harvest mature fields and collect grain', function () {
//     $player = $this->createPlayer();
//     $this->game->start();
//
//     $this->actingAs($player->user);
//
//     // Create team
//     Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
//         ->set('round_properties.'.FarmTeams::key().'.adjective', 'Happy')
//         ->set('round_properties.'.FarmTeams::key().'.noun', 'Farmers')
//         ->call('callClassAction', 'createTeam', 'modifier', FarmTeams::key())
//         ->assertHasNoErrors();
//
//     $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());
//     $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
//     $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
//
//     // Upgrade Farmer skill
//     PlayerUpgradedSkillInFarm::fire(
//         game_id: $this->game->id,
//         modifier_id: $farmSkills->id,
//         player_id: $player->id,
//         skill_name: 'Farmer',
//         xp_cost: 1,
//     );
//
//     Verbs::commit();
//     $player->refresh();
//
//     // Get player's position
//     $playerSpace = collect($farmMap->fresh()->modifier_data)
//         ->filter(fn ($space) => in_array($player->id, $space['player_ids']))
//         ->first();
//
//     // Plant field
//     PlayerPlantedField::fire(
//         game_id: $this->game->id,
//         modifier_id: $farmActions->id,
//         player_id: $player->id,
//         team_id: $player->team_id,
//         x_index: $playerSpace['x-index'],
//         y_index: $playerSpace['y-index'],
//         level: 1,
//     );
//
//     Verbs::commit();
//
//     // Advance to mature stage
//     $challenge = $this->game->fresh()->currentChallenge;
//     $challenge->end();
//     $nextChallenge = $this->game->fresh()->challenges->skip(1)->first();
//     $nextChallenge->start();
//     $nextChallenge->end();
//
//     // Verify field is mature with 5 grain
//     $farmMap = $farmMap->fresh();
//     $space = collect($farmMap->modifier_data)
//         ->firstWhere(fn ($s) => $s['x-index'] === $playerSpace['x-index'] && $s['y-index'] === $playerSpace['y-index']);
//
//     expect($space['field_status']['stage'])->toBe('mature');
//     expect($space['field_status']['quantity'])->toBe(5);
//
//     // Harvest the field
//     $farmActions = $farmActions->fresh();
//     $playerActions = $farmActions->modifier_data[$player->id];
//
//     PlayerHarvestedField::fire(
//         game_id: $this->game->id,
//         modifier_id: $farmActions->id,
//         player_id: $player->id,
//         team_id: $player->team_id,
//         x_index: $playerSpace['x-index'],
//         y_index: $playerSpace['y-index'],
//         field_quantity: 5,
//         player_capacity: $playerActions['grain_capacity'],
//         player_grain: $playerActions['grain'],
//     );
//
//     Verbs::commit();
//
//     // Verify grain was harvested
//     $farmActions = $farmActions->fresh();
//     expect($farmActions->modifier_data[$player->id]['grain'])->toBe(5);
//
//     // Verify field is now empty
//     $farmMap = $farmMap->fresh();
//     $space = collect($farmMap->modifier_data)
//         ->firstWhere(fn ($s) => $s['x-index'] === $playerSpace['x-index'] && $s['y-index'] === $playerSpace['y-index']);
//
//     expect($space['field_status']['quantity'])->toBe(0);
//     expect($space['field_status']['stage'])->toBeNull();
// });

// it('limits grain harvest to player capacity', function () {
//     $player = $this->createPlayer();
//     $this->game->start();
//
//     $this->actingAs($player->user);
//
//     // Create team
//     Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
//         ->set('round_properties.'.FarmTeams::key().'.adjective', 'Happy')
//         ->set('round_properties.'.FarmTeams::key().'.noun', 'Farmers')
//         ->call('callClassAction', 'createTeam', 'modifier', FarmTeams::key())
//         ->assertHasNoErrors();
//
//     $farmSkills = $this->game->fresh()->modifiers->firstWhere('class_key', FarmSkills::key());
//     $farmActions = $this->game->fresh()->modifiers->firstWhere('class_key', FarmActions::key());
//     $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());
//
//     // Upgrade Farmer to level 3
//     for ($i = 1; $i <= 3; $i++) {
//         PlayerUpgradedSkillInFarm::fire(
//             game_id: $this->game->id,
//             modifier_id: $farmSkills->id,
//             player_id: $player->id,
//             skill_name: 'Farmer',
//             xp_cost: $i,
//         );
//     }
//
//     Verbs::commit();
//     $player->refresh();
//
//     $playerSpace = collect($farmMap->fresh()->modifier_data)
//         ->filter(fn ($space) => in_array($player->id, $space['player_ids']))
//         ->first();
//
//     // Plant level 3 field (produces 15 grain)
//     PlayerPlantedField::fire(
//         game_id: $this->game->id,
//         modifier_id: $farmActions->id,
//         player_id: $player->id,
//         team_id: $player->team_id,
//         x_index: $playerSpace['x-index'],
//         y_index: $playerSpace['y-index'],
//         level: 3,
//     );
//
//     Verbs::commit();
//
//     // Advance to mature
//     $challenge = $this->game->fresh()->currentChallenge;
//     $challenge->end();
//     $nextChallenge = $this->game->fresh()->challenges->skip(1)->first();
//     $nextChallenge->start();
//     $nextChallenge->end();
//
//     // Harvest with capacity of 5
//     $farmActions = $farmActions->fresh();
//     $playerActions = $farmActions->modifier_data[$player->id];
//
//     PlayerHarvestedField::fire(
//         game_id: $this->game->id,
//         modifier_id: $farmActions->id,
//         player_id: $player->id,
//         team_id: $player->team_id,
//         x_index: $playerSpace['x-index'],
//         y_index: $playerSpace['y-index'],
//         field_quantity: 15,
//         player_capacity: 5, // Default capacity
//         player_grain: 0,
//     );
//
//     Verbs::commit();
//
//     // Should only harvest 5 grain (capacity limit)
//     $farmActions = $farmActions->fresh();
//     expect($farmActions->modifier_data[$player->id]['grain'])->toBe(5);
//
//     // Field should have 10 grain remaining
//     $farmMap = $farmMap->fresh();
//     $space = collect($farmMap->modifier_data)
//         ->firstWhere(fn ($s) => $s['x-index'] === $playerSpace['x-index'] && $s['y-index'] === $playerSpace['y-index']);
//
//     expect($space['field_status']['quantity'])->toBe(10);
//     expect($space['field_status']['stage'])->toBe('mature'); // Still mature because not fully harvested
// });
