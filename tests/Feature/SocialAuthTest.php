<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can access social auth redirect routes', function () {
    // Test Google (built-in driver)
    $response = $this->get('/auth/google');
    $response->assertRedirect(); // Should redirect to provider

    // Test Discord (custom provider)
    $response = $this->get('/auth/discord');
    $response->assertRedirect(); // Should redirect to provider
});

test('rejects invalid social providers', function () {
    $response = $this->get('/auth/invalid-provider');
    $response->assertNotFound();
});

test('social auth buttons appear on login page', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
    $response->assertSee('Continue with Google');
    $response->assertSee('Continue with Discord');
});

test('social auth buttons appear on register page', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
    $response->assertSee('Continue with Google');
    $response->assertSee('Continue with Discord');
});

test('user model has social auth fields', function () {
    $user = User::factory()->create([
        'provider_id' => '12345',
        'provider_name' => 'google',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);

    expect($user->provider_id)->toBe('12345');
    expect($user->provider_name)->toBe('google');
    expect($user->avatar)->toBe('https://example.com/avatar.jpg');
});

test('avatar_url accessor works correctly', function () {
    // Test with social avatar
    $userWithSocialAvatar = User::factory()->create([
        'avatar' => 'https://example.com/social-avatar.jpg',
    ]);
    expect($userWithSocialAvatar->avatar_url)->toBe(
        'https://example.com/social-avatar.jpg',
    );

    // Test fallback to gravatar
    $userWithoutAvatar = User::factory()->create(['avatar' => null]);
    expect($userWithoutAvatar->avatar_url)->toContain('gravatar.com');
});
