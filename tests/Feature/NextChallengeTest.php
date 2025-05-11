<?php

use App\Models\Challenge;
use Thunk\Verbs\Facades\Verbs;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
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
    config(['app.env' => 'local']);

    Artisan::call('dev:next');

    expect($this->game->refresh()->currentChallenge->class_key === StayOnMessage::key())->toBeTrue();

    Artisan::call('dev:next');

    expect($this->game->refresh()->currentChallenge->class_key === FlattenTheCurve::key())->toBeTrue();
});
