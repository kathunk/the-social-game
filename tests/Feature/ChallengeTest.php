<?php

use App\Models\Challenge;
use Thunk\Verbs\Facades\Verbs;
use App\Challenges\Classes\PyramidScheme;
use App\Challenges\Classes\BaseChallengeClass;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [PyramidScheme::key()],
            'duration' => 10,
        ],
    ];

    $this->mockGameTemplate(challenges: $challenges, type: 'team');
    $this->createGame()->start();
});

it('provides a challenge handler', function () {
    $challenge = Challenge::first();

    expect($challenge->handler())->toBeInstanceOf(BaseChallengeClass::class);
    expect($challenge->state()->handler())->toBeInstanceOf(BaseChallengeClass::class);
});

it('knows what the current challenge is', function () {
    $challenges = Challenge::all();

    $first_challenge = $challenges->first();
    $this->game->refresh();

    expect($first_challenge->status)->toBe('active');
    expect($this->game->currentChallenge->id)->toBe($first_challenge->id);
    expect($this->game->currentChallenge->handler())->toBeInstanceOf(BaseChallengeClass::class);
    expect($this->game->state()->currentChallenge()->id)->toBe($first_challenge->id);
    expect($this->game->state()->currentChallenge()->handler())->toBeInstanceOf(BaseChallengeClass::class);
});
