<?php

use App\Challenges\PeckingOrder\IndividualHighScoreQuiz;
use App\Challenges\PeckingOrder\IndividualLowScoreQuiz;
use App\Events\UserUpdatedNotificationPreferences;
use App\Livewire\PreGameLobby;
use App\Models\User;
use App\Notifications\GameStartedNotification;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function optIntoEmailNotifications(User $user): void
{
    UserUpdatedNotificationPreferences::fire(
        user_id: $user->id,
        phone_number: null,
        default_discord_webhook: null,
        notification_preferences: [
            'notify_on_game_start' => true,
            'notify_on_challenge_start' => true,
            'notify_on_game_end' => true,
            'notify_via_email' => true,
        ],
    );

    Verbs::commit();
}

function breakTheMailer(): void
{
    Mail::extend('broken', function () {
        return new class implements TransportInterface
        {
            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                throw new TransportException('Unable to send an email: Request does not contain a valid Server token. (code 10)');
            }

            public function __toString(): string
            {
                return 'broken';
            }
        };
    });

    config([
        'mail.mailers.broken' => ['transport' => 'broken'],
        'mail.default' => 'broken',
    ]);
}

beforeEach(function () {
    Verbs::commitImmediately();

    $this->mockGameTemplate(
        challenges: [
            [
                'challenge_keys' => [IndividualLowScoreQuiz::key()],
                'duration' => 10,
            ],
            [
                'challenge_keys' => [IndividualHighScoreQuiz::key()],
                'duration' => 10,
            ],
        ],
        type: 'individual',
        has_notifications: true,
    );

    $this->createGame();
    $this->createPlayer();
    $this->createPlayer();
    $this->createPlayer();

    $this->game->players->each(
        fn ($player) => optIntoEmailNotifications($player->user),
    );
});

it('starts the game from the lobby even when the mail transport is broken', function () {
    breakTheMailer();

    Livewire::actingAs($this->game_admin)
        ->test(PreGameLobby::class, ['game' => $this->game])
        ->call('startGame')
        ->assertRedirect(route('game-dashboard', $this->game->id));

    expect($this->game->fresh()->status)->toBe('active');
});

it('queues game start notifications instead of sending them in the request', function () {
    Queue::fake();

    Livewire::actingAs($this->game_admin)
        ->test(PreGameLobby::class, ['game' => $this->game])
        ->call('startGame')
        ->assertRedirect(route('game-dashboard', $this->game->id));

    Queue::assertPushed(
        SendQueuedNotifications::class,
        fn ($job) => $job->notification instanceof GameStartedNotification,
    );
});

it('advances challenges and ends the game even when the mail transport is broken', function () {
    $this->game->start();

    breakTheMailer();

    $first_challenge = $this->game->challenges->firstWhere('round_number', 1);
    $first_challenge->end();
    $first_challenge->next()->start();

    expect($this->game->fresh()->currentChallenge->round_number)->toBe(2);

    $this->game->fresh()->end();

    expect($this->game->fresh()->status)->toBe('ended');
});
