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

it('places players in random spaces on the map after creating a team', function () {
    $player = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player->user);

    // Create a team
    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.FarmTeams::key().'.adjective', 'Happy')
        ->set('round_properties.'.FarmTeams::key().'.noun', 'Farmers')
        ->call('callClassAction', 'createTeam', 'modifier', FarmTeams::key())
        ->assertHasNoErrors();

    $farmMap = $this->game->fresh()->modifiers->firstWhere('class_key', FarmMap::key());

    $playerSpaces = collect($farmMap->modifier_data)
        ->filter(fn ($space) => in_array($player->id, $space['player_ids']));

    expect($playerSpaces->count())->toBe(1);

    $space = $playerSpaces->first();
    expect($space)->toHaveKeys(['x-index', 'y-index', 'type', 'player_ids', 'field_status', 'road_status', 'silo_status']);
});
