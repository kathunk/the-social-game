<?php

use App\Challenges\Classes\PyramidScheme;
use App\Challenges\Classes\StayOnMessage;
use Illuminate\Support\Facades\Date;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [PyramidScheme::key()],
            'duration' => 60,
        ],
        [
            'challenge_keys' => [StayOnMessage::key()],
            'duration' => 60,
        ],
    ];

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'team',
    );

    $this->createGame(starts_at: now()->addMinutes(60));
});

it('the game is upcoming by default', function () {
    expect($this->game->status)->toBe('upcoming');
    expect($this->game->currentChallenge)->toBeNull();
    expect($this->game->challenges->where('status', 'active')->count())->toBe(0);
});

it('starts the game and first challenge', function () {
    Date::setTestNow(now()->addMinutes(59));
    $this->artisan('app:progress-games');
    $this->game->refresh();

    expect($this->game->status)->toBe('upcoming');
    expect($this->game->currentChallenge)->toBeNull();
    expect($this->game->challenges->where('status', 'active')->count())->toBe(0);

    Date::setTestNow(now()->addMinutes(2));
    $this->artisan('app:progress-games');
    $this->game->refresh();

    $challenge_1 = $this->game->challenges->first();
    $challenge_2 = $this->game->challenges->skip(1)->first();

    expect($this->game->status)->toBe('active');
    expect($challenge_1->status)->toBe('active');
    expect($challenge_2->status)->toBe('upcoming');
    expect($this->game->currentChallenge)->not->toBeNull();
    expect($this->game->challenges->where('status', 'active')->count())->toBe(1);
    expect($this->game->currentChallenge->status)->toBe('active');
});

it('progresses from one challenge to the next', function () {
    Date::setTestNow(now()->addMinutes(60));
    $this->artisan('app:progress-games');
    $this->game->refresh();

    $challenges = $this->game->challenges
        ->sortBy('starts_at');

    $first_challenge = $challenges->first();
    $second_challenge = $challenges->skip(1)->first();
    $challenge_changeover_time = $first_challenge->ends_at;

    expect($first_challenge->ends_at->toDateTimeString())->toBe($second_challenge->starts_at->toDateTimeString());

    // end the first challenge and start the second challenge
    Date::setTestNow($challenge_changeover_time);
    $this->artisan('app:progress-games');

    expect($first_challenge->fresh()->status)->toBe('ended');
    expect($second_challenge->fresh()->status)->toBe('active');
    expect($this->game->fresh()->currentChallenge->id)->toBe($second_challenge->id);
});

it('ends the game', function () {
    Date::setTestNow(now()->addMinutes(60));
    $this->artisan('app:progress-games');

    $this->game->challenges->each(function ($challenge) {
        Date::setTestNow($challenge->ends_at);
        $this->artisan('app:progress-games');
    });

    Date::setTestNow($this->game->ends_at);
    $this->artisan('app:progress-games');

    expect($this->game->fresh()->status)->toBe('ended');
});

it('updates the game start time if it was scheduled in the past', function () {
    $this->createGame(starts_at: now()->subMinutes(10));
    $this->artisan('app:progress-games');

    $game = $this->game->fresh();

    expect($game->starts_at->diffInSeconds(now()))->toBeLessThan(60);
    expect($game->challenges->first()->starts_at->diffInSeconds(now()))->toBeLessThan(60);
});
