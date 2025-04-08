<?php

use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();
});

it('sets a new user to the correct status', function () {
    $new_user = $this->createUser();

    expect($new_user->status)->toBe('new');
});



it('accepts a new user', function () {
    $game = $this->createGame();
    $admin = $this->createAdmin();
    $new_user = $this->createUser();
    $player = $this->createPlayer($new_user, $game);

    $new_user->refresh();

    expect($new_user->status)->toBe('accepted');
    expect($player->status)->toBe('active');
    expect($player->user->id)->toBe($new_user->id);
    expect($player->game->id)->toBe($game->id);
    expect($game->players->first()->id)->toBe($player->id);
    expect($new_user->currentPlayer->id)->toBe($player->id);
    expect($new_user->currentGame->id)->toBe($game->id);
});

it('promotes a user to admin', function () {
    $game = $this->createGame();
    $admin = $this->createAdmin();
    $admin->refresh();

    dd($admin->adminGames);

    expect($admin->isAdminOfGame($game))->toBeTrue();
});
