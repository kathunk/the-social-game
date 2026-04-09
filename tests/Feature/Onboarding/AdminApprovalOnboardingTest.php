<?php

use App\Models\Game;
use App\Models\User;
use Database\Seeders\Laracon2025\Laracon2025Seeder;
use Database\Seeders\UserSeeder;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();
    $this->seed(UserSeeder::class);
    $this->seed(Laracon2025Seeder::class);
    $this->game = Game::first();
    $this->admin = $this->game->admins->first();
});

it('sets a new user to the correct status', function () {
    $new_user = User::fromTemplate('Test User', 'test@test.com', 'password', $this->game);
    $new_user->requestToJoinGame($this->game);
    $new_user->refresh();

    expect($new_user->gameApplications->first()->status)->toBe('pending');
});

it('accepts a new user', function () {
    $new_user = User::fromTemplate('New User', 'new@test.com', 'password');
    $new_user->requestToJoinGame($this->game);
    $new_player = $new_user->admitToGame($this->game, $this->admin);
    $new_user->refresh();

    expect($new_user->gameApplications->first()->status)->toBe('accepted');
    expect($new_player->status)->toBe('active');
    expect($new_player->user->id)->toBe($new_user->id);
    expect($new_player->game->id)->toBe($this->game->id);
    expect($new_user->currentPlayer->id)->toBe($new_player->id);
    expect($new_user->currentGame->id)->toBe($this->game->id);
});

it('promotes a user to admin', function () {
    $user = User::fromTemplate('Test User', 'test@test.com', 'password', $this->game);
    $user->promoteToGameAdmin($this->game, $this->admin);
    $user->refresh();

    expect($user->fresh()->isGameAdmin($this->game->fresh()))->toBeTrue();
});

it('rejects a new user', function () {
    $new_user = User::fromTemplate('New User', 'new@test.com', 'password');
    $new_user->requestToJoinGame($this->game);
    $new_user->rejectFromGame($this->game, $this->admin);
    $new_user->refresh();

    expect($new_user->gameApplications->first()->status)->toBe('rejected');
});
