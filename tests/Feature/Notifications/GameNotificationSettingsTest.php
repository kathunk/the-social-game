<?php

use App\Challenges\IndividualFiller;
use App\Livewire\GameDashboard;
use App\Livewire\GameNotificationSettings;
use App\Livewire\ManageGameModePage;
use App\Models\GameMode;
use App\Notifications\ChallengeStartedNotification;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Date::setTestNow('2024-01-01 12:00:00');

    Verbs::commitImmediately();
});

function notificationTestSetup($test, bool $has_notifications): void
{
    $test->mockGameTemplate(
        challenges: [
            ['challenge_keys' => [IndividualFiller::key()], 'duration' => 20],
            ['challenge_keys' => [IndividualFiller::key()], 'duration' => 20],
        ],
        type: 'individual',
        has_notifications: $has_notifications,
    );

    $test->createGame();
    $test->createPlayer();
    $test->game->start();
}

function progressToRoundTwo($test): void
{
    Date::setTestNow(now()->addMinutes(21));

    $test->artisan('app:progress-games');
}

it('shows the notification footer only when the game mode has notifications', function () {
    notificationTestSetup($this, has_notifications: true);

    Livewire::actingAs($this->player->user)
        ->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSeeLivewire(GameNotificationSettings::class);
});

it('hides the notification footer when the game mode has no notifications', function () {
    notificationTestSetup($this, has_notifications: false);

    Livewire::actingAs($this->player->user)
        ->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertDontSeeLivewire(GameNotificationSettings::class);
});

it('defaults the per-game channel toggles from profile settings', function () {
    notificationTestSetup($this, has_notifications: true);

    $this->player->user->update([
        'notification_preferences' => ['notify_via_email' => true],
        'default_discord_webhook' => 'https://discord.com/api/webhooks/x',
    ]);

    Livewire::actingAs($this->player->user->fresh())
        ->test(GameNotificationSettings::class, ['game' => $this->game])
        ->assertSet('channels.notify_via_email', true)
        ->assertSet('channels.notify_via_discord', false);
});

it('persists a per-game channel override when toggled', function () {
    notificationTestSetup($this, has_notifications: true);

    $this->player->user->update([
        'notification_preferences' => ['notify_via_email' => true],
    ]);

    Livewire::actingAs($this->player->user->fresh())
        ->test(GameNotificationSettings::class, ['game' => $this->game])
        ->set('channels.notify_via_email', false);

    $player = $this->player->fresh();

    expect($player->notification_channels['notify_via_email'])->toBeFalse();
    expect($player->wantsNotificationVia('notify_via_email'))->toBeFalse();
    expect($player->state()->notification_channels['notify_via_email'])->toBeFalse();
});

it('sends no notifications for modes without notifications, even with profile prefs on', function () {
    Notification::fake();

    notificationTestSetup($this, has_notifications: false);

    $this->player->user->update([
        'notification_preferences' => ['notify_via_email' => true],
    ]);

    progressToRoundTwo($this);

    expect($this->game->fresh()->challenges->last()->status)->toBe('active');

    Notification::assertNothingSent();
});

it('sends round-start notifications in notification modes, respecting per-game overrides', function () {
    Notification::fake();

    notificationTestSetup($this, has_notifications: true);

    $subscribed = $this->player;
    $subscribed->user->update([
        'notification_preferences' => ['notify_via_email' => true],
    ]);

    $muted = $this->createPlayer();
    $muted->user->update([
        'notification_preferences' => ['notify_via_email' => true],
    ]);

    Livewire::actingAs($muted->user->fresh())
        ->test(GameNotificationSettings::class, ['game' => $this->game])
        ->set('channels.notify_via_email', false);

    progressToRoundTwo($this);

    Notification::assertSentTo($subscribed->user, ChallengeStartedNotification::class);
    Notification::assertNotSentTo($muted->user, ChallengeStartedNotification::class);
});

it('lets superadmins set has_notifications on a game mode', function () {
    $admin = $this->createUser(name: 'super', email: 'super@example.com', encrypted_password: 'password');

    Livewire::actingAs($admin)
        ->test(ManageGameModePage::class)
        ->set('name', 'Notified Mode')
        ->set('description', 'A mode with notifications')
        ->set('pre_game_lobby_message', 'Welcome')
        ->set('has_notifications', true)
        ->call('saveGameMode')
        ->assertHasNoErrors();

    expect(GameMode::where('name', 'Notified Mode')->first()->has_notifications)->toBeTrue();
});
