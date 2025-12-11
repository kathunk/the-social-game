<?php

use App\Models\User;
use Livewire\Volt\Volt;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('notification preferences can be updated', function () {
    $user = User::factory()->create([
        'phone_number' => null,
        'notification_preferences' => [
            'notify_on_game_start' => true,
            'notify_before_challenge_end' => true,
            'notify_on_challenge_start' => true,
            'notify_on_game_end' => true,
        ],
    ]);

    $this->actingAs($user);

    $response = Volt::test('settings.profile')
        ->set('phone_number', '+1234567890')
        ->set('default_discord_webhook', 'https://discord.com/api/webhooks/test')
        ->set('notify_on_game_start', false)
        ->call('updateProfileInformation');

    $response->assertHasNoErrors();

    $user->refresh();

    expect($user->phone_number)->toEqual('+1234567890');
    expect($user->default_discord_webhook)->toEqual('https://discord.com/api/webhooks/test');
    expect($user->notification_preferences['notify_on_game_start'])->toBeFalse();
    expect($user->notification_preferences['notify_on_challenge_start'])->toBeTrue();
});

test('notification preferences are saved when changed', function () {
    $user = User::factory()->create([
        'phone_number' => null,
        'notification_preferences' => [
            'notify_on_game_start' => true,
            'notify_before_challenge_end' => true,
            'notify_on_challenge_start' => true,
            'notify_on_game_end' => true,
        ],
    ]);
    $this->actingAs($user);

    Volt::test('settings.profile')
        ->set('phone_number', '+1234567890')
        ->set('notify_on_game_start', false)
        ->call('updateProfileInformation');

    $user->refresh();

    expect($user->phone_number)->toBe('+1234567890');
    expect($user->notification_preferences['notify_on_game_start'])->toBeFalse();
});

test('notification preferences validate webhook urls', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = Volt::test('settings.profile')
        ->set('default_discord_webhook', 'not-a-valid-url')
        ->call('updateProfileInformation');

    $response->assertHasErrors(['default_discord_webhook']);
});

test('user can check if they want notifications for specific events', function () {
    $user = User::factory()->create([
        'notification_preferences' => [
            'notify_on_game_start' => true,
            'notify_on_challenge_start' => false,
        ],
    ]);

    expect($user->wantsNotificationFor('notify_on_game_start'))->toBeTrue();
    expect($user->wantsNotificationFor('notify_on_challenge_start'))->toBeFalse();
});

test('user can check if notification contact is configured', function () {
    $userWithPhone = User::factory()->create(['phone_number' => '+1234567890']);
    $userWithDiscord = User::factory()->create(['default_discord_webhook' => 'https://discord.com/api/webhooks/test']);
    $userWithoutContact = User::factory()->create();

    expect($userWithPhone->hasNotificationContactConfigured())->toBeTrue();
    expect($userWithDiscord->hasNotificationContactConfigured())->toBeTrue();
    expect($userWithoutContact->hasNotificationContactConfigured())->toBeFalse();
});
