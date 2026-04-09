<?php

use App\Challenges\Classes\Laracon2025\FlattenTheCurve;
use App\Challenges\Classes\PeckingOrder\PyramidScheme;
use App\Challenges\Classes\Laracon2025\StayOnMessage;
use Illuminate\Support\Facades\Artisan;
use Thunk\Verbs\Facades\Verbs;

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

    Artisan::call('dev:next', ['game_id' => $this->game->fresh()->id]);

    expect($this->game->fresh()->currentChallenge->class_key === StayOnMessage::key())->toBeTrue();

    Artisan::call('dev:next', ['game_id' => $this->game->fresh()->id]);

    expect($this->game->fresh()->currentChallenge->class_key === FlattenTheCurve::key())->toBeTrue();
});
