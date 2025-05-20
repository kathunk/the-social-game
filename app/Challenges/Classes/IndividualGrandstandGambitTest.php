<?php

use App\Challenges\Classes\IndividualGrandstandGambit;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs individual grandstand gambit', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualGrandstandGambit::key()],
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

    $challenge = Challenge::first();

    incrementScore(-3, player: $player_1);
    incrementScore(-9, player: $player_2);

    $this->actingAs($player_1->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('challenge_properties.upvote_player_id', $player_3->id)
        ->set('challenge_properties.downvote_player_id', $player_2->id)
        ->call('callChallengeAction', 'vote')->assertHasNoErrors()
        ->call('callChallengeAction', 'gain_5_points')->assertHasNoErrors();

    $this->actingAs($player_2->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('challenge_properties.upvote_player_id', $player_4->id)
        ->set('challenge_properties.downvote_player_id', $player_1->id)
        ->call('callChallengeAction', 'vote')->assertHasNoErrors()
        ->call('callChallengeAction', 'gain_5_points')->assertHasNoErrors();

    $challenge->refresh();
    $challenge->end();

    // player 1 gains 5 points to get to 1, and thus busts
    expect($player_1->fresh()->score)->toBe(0);

    // player 2 benefits from the grandstand gambit
    expect($player_2->fresh()->score)->toBe(-5);

    // player 3 and 4 did nothing special
    expect($player_3->fresh()->score)->toBe(1);
    expect($player_4->fresh()->score)->toBe(1);

    // players 3 and 4 get a bonus point
    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(0);
    expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(-5);
    expect($player_3->fresh()->state()->score(include_hidden: true))->toBe(2);
    expect($player_4->fresh()->state()->score(include_hidden: true))->toBe(2);
});
