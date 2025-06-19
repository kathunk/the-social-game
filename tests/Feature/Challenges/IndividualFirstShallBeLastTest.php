<?php

use App\Challenges\Classes\IndividualFirstShallBeLast;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs individual steal the bacon', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualFirstShallBeLast::key()],
            'duration' => 10,
        ],
    ];

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'individual',
    );

    $this->createGame();

    $player_1 = $this->createPlayer();
    $player_2 = $this->createPlayer();
    $player_3 = $this->createPlayer();
    $player_4 = $this->createPlayer();

    $this->game->start();

    incrementScore(10, player: $player_1);
    incrementScore(10, player: $player_2);
    incrementScore(-10, player: $player_3);
    incrementScore(-10, player: $player_4);

    $this->game->fresh()->challenges->first()->end();

    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(5);
    expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(5);
    expect($player_3->fresh()->state()->score(include_hidden: true))->toBe(-5);
    expect($player_4->fresh()->state()->score(include_hidden: true))->toBe(-5);
});
