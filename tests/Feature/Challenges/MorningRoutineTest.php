<?php

use App\Challenges\MorningRoutine\MorningRoutineRound;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('initializes all players in the hallway', function () {
    Verbs::commitImmediately();

    $this->mockGameTemplate(
        challenges: [['challenge_keys' => [MorningRoutineRound::key()], 'duration' => 5]],
        type: 'individual',
    );

    $this->createGame();

    $player_1 = $this->createPlayer();
    $player_2 = $this->createPlayer();
    $player_3 = $this->createPlayer();

    $this->game->start();

    $challenge = Challenge::first();
    $data = $challenge->challenge_data;

    expect($data['player_locations'][$player_1->id])->toBe('hallway');
    expect($data['player_locations'][$player_2->id])->toBe('hallway');
    expect($data['player_locations'][$player_3->id])->toBe('hallway');
});

it('allows a player to enter an empty room', function () {
    Verbs::commitImmediately();

    $this->mockGameTemplate(
        challenges: [['challenge_keys' => [MorningRoutineRound::key()], 'duration' => 5]],
        type: 'individual',
    );

    $this->createGame();

    $player_1 = $this->createPlayer();
    $player_2 = $this->createPlayer();

    $this->game->start();

    $challenge = Challenge::first();

    $this->actingAs($player_1->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.$challenge->class_key.'.room', 'kitchen')
        ->call('callClassAction', 'enterRoom', 'challenge', $challenge->class_key)
        ->assertHasNoErrors();

    $challenge->refresh();
    expect($challenge->challenge_data['player_locations'][$player_1->id])->toBe('kitchen');
    expect($challenge->challenge_data['player_locations'][$player_2->id])->toBe('hallway');
});

it('prevents entering an occupied room', function () {
    Verbs::commitImmediately();

    $this->mockGameTemplate(
        challenges: [['challenge_keys' => [MorningRoutineRound::key()], 'duration' => 5]],
        type: 'individual',
    );

    $this->createGame();

    $player_1 = $this->createPlayer();
    $player_2 = $this->createPlayer();

    $this->game->start();

    $challenge = Challenge::first();

    // Player 1 enters the bathroom
    $this->actingAs($player_1->user);
    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.$challenge->class_key.'.room', 'bathroom')
        ->call('callClassAction', 'enterRoom', 'challenge', $challenge->class_key)
        ->assertHasNoErrors();

    // Player 2 tries to enter the same room
    $this->actingAs($player_2->user);
    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.$challenge->class_key.'.room', 'bathroom')
        ->call('callClassAction', 'enterRoom', 'challenge', $challenge->class_key)
        ->assertHasErrors('action_error');

    $challenge->refresh();
    expect($challenge->challenge_data['player_locations'][$player_2->id])->toBe('hallway');
});

it('allows a player to exit a room back to the hallway', function () {
    Verbs::commitImmediately();

    $this->mockGameTemplate(
        challenges: [['challenge_keys' => [MorningRoutineRound::key()], 'duration' => 5]],
        type: 'individual',
    );

    $this->createGame();

    $player_1 = $this->createPlayer();
    $player_2 = $this->createPlayer();

    $this->game->start();

    $challenge = Challenge::first();

    $this->actingAs($player_1->user);

    // Enter room
    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.$challenge->class_key.'.room', 'study')
        ->call('callClassAction', 'enterRoom', 'challenge', $challenge->class_key)
        ->assertHasNoErrors();

    $challenge->refresh();
    expect($challenge->challenge_data['player_locations'][$player_1->id])->toBe('study');

    // Exit room
    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->call('callClassAction', 'exitRoom', 'challenge', $challenge->class_key)
        ->assertHasNoErrors();

    $challenge->refresh();
    expect($challenge->challenge_data['player_locations'][$player_1->id])->toBe('hallway');
});
