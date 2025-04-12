<?php

use App\Models\User;
use App\Models\GameApplication;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();
    $this->game = $this->createGame();
});

it('sets a new user to the correct status', function () {
    $new_user = User::fromTemplate('Test User', 'test@test.com', 'password', $this->game);
    $new_user->refresh();

    expect($new_user->gameApplications->first()->status)->toBe('pending');
});

it('accepts a new user', function () {
    $game = $this->createGame();
    $admin = User::fromTemplate('Admin User', 'admin@test.com', 'password', $this->game)->promoteToAdmin($game);
    $new_user = User::fromTemplate('New User', 'new@test.com', 'password', $this->game);
    $new_player = $new_user->admitToGame($game, $admin);
    $new_user->refresh();

    expect($new_user->gameApplications->first()->status)->toBe('accepted');
    expect($new_player->status)->toBe('active');
    expect($new_player->user->id)->toBe($new_user->id);
    expect($new_player->game->id)->toBe($game->id);
    expect($game->players->first()->id)->toBe($new_player->id);
    expect($new_user->currentPlayer->id)->toBe($new_player->id);
    expect($new_user->currentGame->id)->toBe($game->id);
});

it('promotes a user to admin', function () {
    $user = User::fromTemplate('Test User', 'test@test.com', 'password', $this->game);
    $user->promoteToAdmin($this->game);
    $user->refresh();

    expect($user->is_admin)->toBeTrue();
});

it('rejects a new user', function () {
    $game = $this->createGame();
    $admin = User::fromTemplate('Admin User', 'admin@test.com', 'password', $this->game)->promoteToAdmin($game);
    $new_user = User::fromTemplate('New User', 'new@test.com', 'password', $this->game);
    $new_user->rejectFromGame($game, $admin);
    $new_user->refresh();

    expect($new_user->gameApplications->first()->status)->toBe('rejected');
    // @todo I suppose we should be testing the values on the GameState as well
});