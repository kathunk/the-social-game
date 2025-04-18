<?php

use App\Challenges\Classes\BaseChallengeClass;
use App\Models\Challenge;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();
    $this->game = $this->createGame();
    $this->game->fresh()->start();
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
