<?php

use App\Challenges\Classes\IndividualFiller;
use App\Livewire\PreGameLobby;
use Illuminate\Support\Facades\Date;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
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

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'individual',
    );

    $this->createGame(starts_at: now()->addMinutes(60));
});

it('sets the appropriate challenge lengths for overrides', function () {
    expect($this->game->total_duration)->toBe(50);
    expect($this->game->ends_at->toDateTimeString())->toBe(now()->addMinutes(110)->toDateTimeString());

    Livewire::actingAs($this->game->players->first()->user)
        ->test(PreGameLobby::class, [
            'game' => $this->game,
        ])
        ->set('challenge_length_override', 10)
        ->set('use_challenge_length_override', true)
        ->call('updateGameSettings');

    $this->game->refresh();

    expect($this->game->total_duration)->toBe(20);
    expect($this->game->ends_at->toDateTimeString())->toBe(now()->addMinutes(80)->toDateTimeString());

    Date::setTestNow($this->game->starts_at);
    $this->artisan('app:progress-games');

    expect($this->game->fresh()->challenges->first()->starts_at->toDateTimeString())
        ->toBe(now()->toDateTimeString());

    expect($this->game->fresh()->challenges->first()->ends_at->toDateTimeString())
        ->toBe(now()->addMinutes(10)->toDateTimeString());

    expect($this->game->fresh()->challenges->last()->starts_at->toDateTimeString())
        ->toBe(now()->addMinutes(10)->toDateTimeString());

    expect($this->game->fresh()->challenges->last()->ends_at->toDateTimeString())
        ->toBe(now()->addMinutes(20)->toDateTimeString());
});
