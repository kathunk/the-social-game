<?php

use App\Challenges\Classes\IndividualChoosePointsOrHidden;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs individual choose points or hidden', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualChoosePointsOrHidden::key()],
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

    $this->actingAs($player_1->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('challenge_properties.upvote_player_id', $player_3->id)
        ->set('challenge_properties.downvote_player_id', $player_2->id)
        ->call('callChallengeAction', 'vote')->assertHasNoErrors()
        ->call('callChallengeAction', 'choose_points')->assertHasNoErrors();

    $this->actingAs($player_2->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('challenge_properties.upvote_player_id', $player_4->id)
        ->set('challenge_properties.downvote_player_id', $player_1->id)
        ->call('callChallengeAction', 'vote')->assertHasNoErrors()
        ->call('callChallengeAction', 'choose_points')->assertHasNoErrors();

    $this->actingAs($player_3->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('challenge_properties.upvote_player_id', $player_4->id)
        ->set('challenge_properties.downvote_player_id', $player_2->id)
        ->call('callChallengeAction', 'vote')->assertHasNoErrors()
        ->call('callChallengeAction', 'choose_points')->assertHasNoErrors();

    $this->actingAs($player_4->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('challenge_properties.upvote_player_id', $player_3->id)
        ->set('challenge_properties.downvote_player_id', $player_1->id)
        ->call('callChallengeAction', 'vote')->assertHasNoErrors()
        ->call('callChallengeAction', 'choose_hidden')->assertHasNoErrors();

    $challenge->refresh();
    $challenge->end();

    // players 1, 2, and 3 split 10 points, rounded down: 3 points each

    // visible scores show this
    expect($player_1->fresh()->score)->toBe(1);
    expect($player_2->fresh()->score)->toBe(1);
    expect($player_3->fresh()->score)->toBe(5);
    expect($player_4->fresh()->score)->toBe(2);

    // player 4 got all 4 hidden points alone

    // but with hidden scores included, we see this
    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(1);
    expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(1);
    expect($player_3->fresh()->state()->score(include_hidden: true))->toBe(5);
    expect($player_4->fresh()->state()->score(include_hidden: true))->toBe(7);
});
