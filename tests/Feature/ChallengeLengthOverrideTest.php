<?php

use App\Challenges\IndividualFiller;
use App\Livewire\PreGameLobby;
use Illuminate\Support\Facades\Date;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Freeze time at the beginning of each test
    Date::setTestNow('2024-01-01 12:00:00');

    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualFiller::key()],
            'duration' => 20,
        ],
        [
            'challenge_keys' => [IndividualFiller::key()],
            'duration' => 30,
        ],
    ];

    $this->mockGameTemplate(challenges: $challenges, type: 'individual');

    $this->createGame(starts_at: now()->addMinutes(60));
});

it('sets the appropriate challenge lengths for overrides', function () {
    $baseTime = now();

    expect($this->game->total_duration)->toBe(50);
    expect($this->game->ends_at->toDateTimeString())->toBe(
        $baseTime->copy()->addMinutes(110)->toDateTimeString()
    );

    Livewire::actingAs($this->game->players->first()->user)
        ->test(PreGameLobby::class, [
            'game' => $this->game,
        ])
        ->set('challenge_length_override', 10)
        ->set('use_challenge_length_override', true)
        ->call('updateGameSettings');

    $this->game->refresh();

    expect($this->game->fresh()->total_duration)->toBe(20);
    expect($this->game->ends_at->toDateTimeString())->toBe(
        $baseTime->copy()->addMinutes(80)->toDateTimeString()
    );

    // Set time to when the game starts
    Date::setTestNow($this->game->starts_at);
    $gameStartTime = now();

    $this->artisan('app:progress-games');

    expect(
        $this->game->fresh()->challenges->first()->starts_at->toDateTimeString()
    )->toBe($gameStartTime->toDateTimeString());

    expect(
        $this->game->fresh()->challenges->first()->ends_at->toDateTimeString()
    )->toBe($gameStartTime->copy()->addMinutes(10)->toDateTimeString());

    expect(
        $this->game->fresh()->challenges->last()->starts_at->toDateTimeString()
    )->toBe($gameStartTime->copy()->addMinutes(10)->toDateTimeString());

    expect(
        $this->game->fresh()->challenges->last()->ends_at->toDateTimeString()
    )->toBe($gameStartTime->copy()->addMinutes(20)->toDateTimeString());
});
