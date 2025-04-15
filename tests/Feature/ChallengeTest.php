<?php

use App\Models\Challenge;
use Thunk\Verbs\Facades\Verbs;
use App\Challenges\Classes\BaseChallengeClass;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();
    $this->game = $this->createGame();
});

it('provides a challenge handler', function () {
    $challenge = Challenge::first();

    expect($challenge->handler())->toBeInstanceOf(BaseChallengeClass::class);
    expect($challenge->state()->handler())->toBeInstanceOf(BaseChallengeClass::class);

    dd($challenge->handler());
});
