<?php

use App\Models\Challenge;
use Thunk\Verbs\Facades\Verbs;
use App\Events\PlayerSubmittedQuizGuess;
use App\Events\PlayerSubmittedPeckingOrderBallot;
use App\Challenges\Classes\IndividualLargestDecreaseQuiz;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs the individual largest decrease quiz', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualLargestDecreaseQuiz::key()],
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

    PlayerSubmittedPeckingOrderBallot::fire(
        player_id: $player_1->id,
        challenge_id: $challenge->id,
        game_id: $this->game->id,
        downvote_player_id: $player_3->id,
        upvote_player_id: $player_2->id,
    );

    PlayerSubmittedPeckingOrderBallot::fire(
        player_id: $player_2->id,
        challenge_id: $challenge->id,
        game_id: $this->game->id,
        downvote_player_id: $player_4->id,
        upvote_player_id: $player_1->id,
    );

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
    expect($player_1->fresh()->score)->toBe(1);
    expect($player_2->fresh()->score)->toBe(1);
    expect($player_3->fresh()->score)->toBe(-1);
    expect($player_4->fresh()->score)->toBe(-1);

    // but with hidden scores included, we see this
    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(2);
    expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(2);
    expect($player_3->fresh()->state()->score(include_hidden: true))->toBe(-1);
    expect($player_4->fresh()->state()->score(include_hidden: true))->toBe(-1);
});
