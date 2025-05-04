<?php

use App\Models\Challenge;
use Thunk\Verbs\Facades\Verbs;
use App\Events\PlayerSubmittedPeckingOrderBallot;
use App\Challenges\Classes\IndividualHighScoreQuiz;



uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('collects and applies pecking order ballots', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualHighScoreQuiz::key()],
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

    $this->game->start();

    $challenge = Challenge::first();

    PlayerSubmittedPeckingOrderBallot::fire(
        player_id: $player_1->id,
        challenge_id: $challenge->id,
        game_id: $this->game->id,
        downvote_player_id: $player_2->id,
        upvote_player_id: $player_3->id,
    );

    $challenge->refresh();

    expect($challenge->challenge_data['votes'][$player_1->id]['downvote_player_id'])->toBe($player_2->id);
    expect($challenge->challenge_data['votes'][$player_1->id]['upvote_player_id'])->toBe($player_3->id);

    $challenge->end();

    expect($player_2->fresh()->score)->toBe(-1);
    expect($player_3->fresh()->score)->toBe(1);
});
