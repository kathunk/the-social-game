<?php

use App\Models\Challenge;
use Thunk\Verbs\Facades\Verbs;
use App\Challenges\Classes\PyramidScheme;
use App\Challenges\Classes\StayOnMessage;
use App\Challenges\Classes\FlattenTheCurve;
use App\Challenges\Classes\BaseChallengeClass;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [PyramidScheme::key()],
            'duration' => 10,
        ],
        [
            'challenge_keys' => [StayOnMessage::key()],
            'duration' => 10,
        ],
        [
            'challenge_keys' => [FlattenTheCurve::key()],
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

it('sets the correct challenge times', function () {
    $challenges = Challenge::all();

    $first = $challenges->first();
    $second = $challenges->skip(1)->first();
    $third = $challenges->skip(2)->first();

    expect($first->starts_at->toDateTimeString())->toEqual($this->game->starts_at->toDateTimeString());
    expect($first->ends_at->toDateTimeString())->toEqual($this->game->starts_at->copy()->addMinutes(10)->toDateTimeString());
    expect($second->starts_at->toDateTimeString())->toEqual($first->ends_at->toDateTimeString());
    expect($second->ends_at->toDateTimeString())->toEqual($second->starts_at->copy()->addMinutes(10)->toDateTimeString());
    expect($third->starts_at->toDateTimeString())->toEqual($second->ends_at->toDateTimeString());
    expect($third->ends_at->toDateTimeString())->toEqual($third->starts_at->copy()->addMinutes(10)->toDateTimeString());
});
