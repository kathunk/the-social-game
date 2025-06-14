<?php

use App\Challenges\Classes\IndividualFiller;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();
});

it('creates bots', function () {
    Artisan::call('app:create-bots', ['amount' => 10]);

    $this->assertDatabaseHas('users', [
        'name' => 'Bot 1',
        'email' => 'bot1@bot.bot',
    ]);

    $this->assertDatabaseHas('users', [
        'name' => 'Bot 10',
        'email' => 'bot10@bot.bot',
    ]);

    expect(User::where('email', 'like', 'bot%@bot.bot')->count())->toBe(10);
});

it('handles adding extra bots without redundancy', function () {
    Artisan::call('app:create-bots', ['amount' => 10]);
    Artisan::call('app:create-bots', ['amount' => 5]);

    $this->assertDatabaseHas('users', [
        'name' => 'Bot 11',
        'email' => 'bot11@bot.bot',
    ]);

    $this->assertDatabaseHas('users', [
        'name' => 'Bot 15',
        'email' => 'bot15@bot.bot',
    ]);

    expect(User::where('email', 'like', 'bot%@bot.bot')->count())->toBe(15);
});

it('can add bots to a game', function () {

    $challenges = [
        [
            'challenge_keys' => [IndividualFiller::key()],
            'duration' => 10,
        ],
    ];

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'individual',
    );

    $this->createGame();

    Artisan::call('app:create-bots', ['amount' => 5]);
    Artisan::call('app:create-bots', ['amount' => 5]);
    Artisan::call('app:fill-game-with-bots', ['game_id' => $this->game->id, 'amount' => 10]);

    expect($this->game->fresh()->players->count())->toBe(11);
});
