<?php

use Livewire\Livewire;
use App\Models\Challenge;
use Thunk\Verbs\Facades\Verbs;
use App\Livewire\GameDashboard;
use App\Events\PlayerSubmittedQuizGuess;
use App\Events\PlayerSubmittedPeckingOrderBallot;
use App\Challenges\Classes\IndividualMostTotalVotesQuiz;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs the individual high score quiz', function () {
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
    ->call('callChallengeAction', 'vote', [
        'challenge_properties' => [
            'downvote_player_id' => $player_2->id, 
            'upvote_player_id' => $player_4->id
        ]
    ])
    ->call('callChallengeAction', 'guess', [
        'challenge_properties' => [
            'guess_player_id' => $player_1->id
        ]
    ]);

    $this->actingAs($player_2->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
    ->call('callChallengeAction', 'vote', [
        'challenge_properties' => [
            'downvote_player_id' => $player_4->id, 
            'upvote_player_id' => $player_3->id
        ]
    ])
    ->call('callChallengeAction', 'guess', [
        'challenge_properties' => [
            'guess_player_id' => $player_3->id
        ]
    ]);

    dd([
        'player_1' => $player_1->id,
        'player_2' => $player_2->id,
        'player_3' => $player_3->id,
        'player_4' => $player_4->id,
        'challenge_data' => $challenge->fresh()->challenge_data,
    ]);

    $challenge->refresh();
    $challenge->end();

    // but with hidden scores included, we see this
    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(1);
    expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(-1);
    expect($player_3->fresh()->state()->score(include_hidden: true))->toBe(1);
    expect($player_4->fresh()->state()->score(include_hidden: true))->toBe(0);
});
