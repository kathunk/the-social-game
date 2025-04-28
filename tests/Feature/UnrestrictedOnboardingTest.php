<?php

use App\Models\Game;
use App\Models\User;
use App\Models\GameTemplate;
use Thunk\Verbs\Facades\Verbs;
use Database\Seeders\UserSeeder;
use Database\Seeders\Laracon2025Seeder;
use Database\Seeders\GameTemplateSeeder;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();
    $this->seed(UserSeeder::class);
    $this->seed(GameTemplateSeeder::class);
    $this->admin = User::first();
});

it('freely allows users to join games where admin approval is not required', function () {
    $game = Game::fromTemplate(
        template: GameTemplate::first(),
        starts_at: now(),
        user: $this->admin,
        is_public: true,
        requires_admin_approval_to_join: false,
    );

    $new_user = User::fromTemplate('New User', 'new@test.com', 'password');
    $new_user->requestToJoinGame($game);
    $new_user->refresh();

    expect($new_user->gameApplications->first()->status)->toBe('accepted');
    expect($new_user->currentGame->id)->toBe($game->id);
    expect($game->fresh()->players->count())->toBe(2);
});
