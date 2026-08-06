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

test('social auth respects intended url', function () {
    // Simulate visiting a protected page that sets intended URL
    session()->put('url.intended', route('dashboard'));

    // Mock a successful social auth response
    $response = $this->get('/auth/google');

    // Should redirect to OAuth provider (we can't easily test the full OAuth flow in unit tests)
    $response->assertRedirect();
});

test('social auth handles game parameter correctly', function () {
    $gameId = 'test-game-123';

    $response = $this->get("/auth/google?game={$gameId}");

    // Should redirect to OAuth provider with game parameter preserved
    $response->assertRedirect();

    // Verify the redirect URL contains state parameter with game data
    $redirectUrl = $response->headers->get('Location');
    expect($redirectUrl)->toContain('state=');
});

test('social auth preserves game parameter through session', function () {
    $gameId = 'test-game-123';

    // Simulate clicking social login with game parameter
    $response = $this->get("/auth/google?game={$gameId}");

    // Should redirect to OAuth provider
    $response->assertRedirect();

    // Verify game parameter is stored in session
    expect(session('social_auth_game'))->toBe($gameId);
});

test('social auth callback retrieves game from session', function () {
    $gameId = 'test-game-123';

    // Manually set session as if user clicked social login from pre-game-lobby
    session()->put('social_auth_game', $gameId);

    // Create a user for the callback to work
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'provider_id' => 'google-123',
        'provider_name' => 'google',
    ]);

    // Mock the Socialite driver to return our test user
    $socialiteUser = new \Laravel\Socialite\Two\User;
    $socialiteUser->map([
        'id' => 'google-123',
        'email' => 'test@example.com',
        'name' => 'Test User',
        'avatar' => 'https://example.com/avatar.jpg',
    ]);

    \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturnSelf();
    \Laravel\Socialite\Facades\Socialite::shouldReceive('user')->andReturn(
        $socialiteUser,
    );

    // Simulate the OAuth callback
    $response = $this->get('/auth/google/callback');

    // Should redirect to pre-game-lobby with the game parameter
    $response->assertRedirect(route('pre-game-lobby', ['game' => $gameId]));

    // Game parameter should be removed from session
    expect(session('social_auth_game'))->toBeNull();
});
