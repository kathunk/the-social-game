<?php

use App\Models\Challenge;
use Thunk\Verbs\Facades\Verbs;
use App\Events\PlayerSubmittedQuizGuess;
use App\Challenges\Classes\IndividualSpecificScoreQuiz;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs the individual guess specific score quiz', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualSpecificScoreQuiz::key()],
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

    // player 1 is correct
    PlayerSubmittedQuizGuess::fire(
        player_id: $player_1->id,
        challenge_id: $challenge->id,
        game_id: $this->game->id,
        guess: ['guess_score' => 1],
    );

    // player 2 is correct
    PlayerSubmittedQuizGuess::fire(
        player_id: $player_2->id,
        challenge_id: $challenge->id,
        game_id: $this->game->id,
        guess: ['guess_score' => -1],
    );

    // player 3 is incorrect
    PlayerSubmittedQuizGuess::fire(
        player_id: $player_3->id,
        challenge_id: $challenge->id,
        game_id: $this->game->id,
        guess: ['guess_score' => 2],
    );

    // player 4 is incorrect
    PlayerSubmittedQuizGuess::fire(
        player_id: $player_4->id,
        challenge_id: $challenge->id,
        game_id: $this->game->id,
        guess: ['guess_score' => -2],
    );

    $challenge->refresh();
    $challenge->end();

    // visible scores show this
    expect($player_1->fresh()->score)->toBe(0);
    expect($player_2->fresh()->score)->toBe(0);
    expect($player_3->fresh()->score)->toBe(0);
    expect($player_4->fresh()->score)->toBe(0);

    // but with hidden scores included, we see this
    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(1);
    expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(1);
    expect($player_3->fresh()->state()->score(include_hidden: true))->toBe(0);
    expect($player_4->fresh()->state()->score(include_hidden: true))->toBe(0);
});
