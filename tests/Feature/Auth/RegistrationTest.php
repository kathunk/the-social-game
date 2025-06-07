<?php

use App\Models\User;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    expect(Hash::check('password', User::first()->password))->toBeTrue();
});

test('replays do not change passwords', function () {
    $response = Volt::test('auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register');

    Artisan::call('db:reset-data');
    Artisan::call('verbs:replay');

    expect(Hash::check('password', User::first()->password))->toBeTrue();
});