<?php

use App\Challenges\IndividualFiller;
use App\Notifications\ChallengeStartedNotification;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();
});

function makeStartedGameWithDuration(int $duration): array
{
    $challenges = [
        [
            'challenge_keys' => [IndividualFiller::key()],
            'duration' => $duration,
        ],
    ];

    test()->mockGameTemplate(challenges: $challenges, type: 'individual');
    test()->createGame(starts_at: now());
    test()->game->start();

    $game = test()->game->fresh();

    return [$game, $game->challenges->first()];
}

it('renders every channel without an end time for untimed challenges', function () {
    [$game, $challenge] = makeStartedGameWithDuration(0);

    expect($challenge->ends_at)->toBeNull();

    $notification = new ChallengeStartedNotification($challenge, $game);

    $mail = $notification->toMail($this->game_admin)->render();
    expect((string) $mail)->not->toContain('The challenge ends');

    $discord = $notification->toDiscord($this->game_admin);
    expect($discord['embeds'][0]['description'])->not->toContain('The challenge ends');

    $telegram = $notification->toTelegram($this->game_admin);
    expect($telegram)->not->toContain('The challenge ends');

    $webPush = $notification->toWebPush($this->game_admin);
    expect($webPush['body'])->not->toContain('The challenge ends');
});

it('renders every channel with an end time for timed challenges', function () {
    [$game, $challenge] = makeStartedGameWithDuration(20);

    expect($challenge->ends_at)->not->toBeNull();

    $notification = new ChallengeStartedNotification($challenge, $game);

    $mail = $notification->toMail($this->game_admin)->render();
    expect((string) $mail)->toContain('The challenge ends');

    $discord = $notification->toDiscord($this->game_admin);
    expect($discord['embeds'][0]['description'])->toContain('The challenge ends');

    $telegram = $notification->toTelegram($this->game_admin);
    expect($telegram)->toContain('The challenge ends');

    $webPush = $notification->toWebPush($this->game_admin);
    expect($webPush['body'])->toContain('The challenge ends');
});
