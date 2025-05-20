<?php

use App\Challenges\Classes\IndividualMostTotalVotesQuiz;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs the most total votes quiz', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualMostTotalVotesQuiz::key()],
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
        ->set('challenge_properties.upvote_player_id', $player_4->id)
        ->set('challenge_properties.downvote_player_id', $player_2->id)
        ->call('callChallengeAction', 'vote')->assertHasNoErrors()
        ->set('challenge_properties.guess_player_id', $player_4->id)
        ->call('callChallengeAction', 'guess')->assertHasNoErrors();

    $this->actingAs($player_2->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('challenge_properties.upvote_player_id', $player_3->id)
        ->set('challenge_properties.downvote_player_id', $player_4->id)
        ->call('callChallengeAction', 'vote')->assertHasNoErrors()
        ->set('challenge_properties.guess_player_id', $player_3->id)
        ->call('callChallengeAction', 'guess')->assertHasNoErrors();

    $challenge->refresh();
    $challenge->end();

    // with secret points hidden, we see this
    expect($player_1->fresh()->score)->toBe(0);
    expect($player_2->fresh()->score)->toBe(-1);
    expect($player_3->fresh()->score)->toBe(1);
    expect($player_4->fresh()->score)->toBe(0);

    // but with hidden scores included, we see this
    expect($player_1->fresh()->hidden_score)->toBe(1);
    expect($player_2->fresh()->hidden_score)->toBe(-1);
    expect($player_3->fresh()->hidden_score)->toBe(1);
    expect($player_4->fresh()->hidden_score)->toBe(0);
});
