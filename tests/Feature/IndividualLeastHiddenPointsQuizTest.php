<?php

use App\Models\Challenge;
use Thunk\Verbs\Facades\Verbs;
use App\Events\PlayerSubmittedQuizGuess;
use App\Events\PlayerSubmittedPeckingOrderBallot;
use App\Challenges\Classes\IndividualFewestHiddenPointQuiz;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs the individual fewest hidden points quiz', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualFewestHiddenPointQuiz::key()],
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

    incrementScore(player: $player_1, points: 10);
    incrementScore(player: $player_1, points: 10, is_hidden: true);
    incrementScore(player: $player_2, points: 20);
    incrementScore(player: $player_2, points: 10, is_hidden: true);
    incrementScore(player: $player_3, points: 30);
    incrementScore(player: $player_4, points: 40);

    // player 3 and player 1 are tied at 1 point

    // player 1 is correct
    PlayerSubmittedQuizGuess::fire(
        player_id: $player_1->id,
        challenge_id: $challenge->id,
        game_id: $this->game->id,
        guess: ['guess_player_id' => $player_3->id],
    );

    // player 2 is correct
    PlayerSubmittedQuizGuess::fire(
        player_id: $player_2->id,
        challenge_id: $challenge->id,
        game_id: $this->game->id,
        guess: ['guess_player_id' => $player_4->id],
    );

    // player 3 is incorrect
    PlayerSubmittedQuizGuess::fire(
        player_id: $player_3->id,
        challenge_id: $challenge->id,
        game_id: $this->game->id,
        guess: ['guess_player_id' => $player_1->id],
    );

    $challenge->refresh();
    $challenge->end();

    // visible scores show this
    expect($player_1->fresh()->score)->toBe(10);
    expect($player_2->fresh()->score)->toBe(20);
    expect($player_3->fresh()->score)->toBe(30);
    expect($player_4->fresh()->score)->toBe(40);

    // but with hidden scores included, we see this
    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(21);
    expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(31);
    expect($player_3->fresh()->state()->score(include_hidden: true))->toBe(30);
    expect($player_4->fresh()->state()->score(include_hidden: true))->toBe(40);
});
