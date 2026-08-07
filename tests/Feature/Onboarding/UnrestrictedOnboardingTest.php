<?php

use App\Challenges\PeckingOrder\IndividualLowScoreQuiz;
use App\Models\User;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();

    $this->mockGameTemplate(
        challenges: [['challenge_keys' => [IndividualLowScoreQuiz::key()], 'duration' => 10]],
        type: 'individual',
    );
});

it('freely allows users to join games where admin approval is not required', function () {
    $game = $this->createGame(requires_admin_approval_to_join: false);

    $new_user = User::fromTemplate('New User', 'new@test.com', 'password');
    $new_user->requestToJoinGame($game);
    $new_user->refresh();

    expect($new_user->gameApplications->first()->status)->toBe('accepted');
    expect($new_user->currentGame->id)->toBe($game->id);
    expect($game->fresh()->players->count())->toBe(2);
});
