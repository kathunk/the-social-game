<?php

use App\Challenges\Classes\IndividualHighScoreQuiz;
use App\Challenges\Classes\TierListConstructionPhase;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use App\Modifiers\Classes\TierListModifier;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [TierListConstructionPhase::key()],
            'duration' => null,
        ],
    ];

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'individual',
        modifiers: [TierListModifier::key()]
    );

    $this->createGame();

    $this->player_1 = $this->createPlayer();
    $this->player_2 = $this->createPlayer();
    $this->player_3 = $this->createPlayer();
    $this->player_4 = $this->createPlayer();

    $this->game->start();

    $this->construction_challenge = $this->game->fresh()->challenges->first();
});

it('allows challenges that have no duration to be ended', function () {
    expect($this->construction_challenge->starts_at)->not()->toBeNull();
    expect($this->construction_challenge->ends_at)->toBeNull();
});

it('selects 3 categories', function () {
    dd($this->construction_challenge->challenge_data['categories']);
});