<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware('guest')->group(function () {
    Volt::route('login', 'auth.login')->name('login');

    Volt::route('register', 'auth.register')->name('register');

    Volt::route('forgot-password', 'auth.forgot-password')->name(
        'password.request',
    );

    Volt::route('reset-password/{token}', 'auth.reset-password')->name(
        'password.reset',
    );

    // Social authentication routes
    Route::get('auth/{provider}', [
        SocialAuthController::class,
        'redirectToProvider',
    ])->name('auth.social.redirect');

    Route::get('auth/{provider}/callback', [
        SocialAuthController::class,
        'handleProviderCallback',
    ])->name('auth.social.callback');
});

Route::middleware('auth')->group(function () {
    Volt::route('verify-email', 'auth.verify-email')->name(
        'verification.notice',
    );

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Volt::route('confirm-password', 'auth.confirm-password')->name(
        'password.confirm',
    );
});

Route::post('logout', App\Livewire\Actions\Logout::class)->name('logout');

// Debug route for testing social auth flow
Route::get('debug/social-auth', function () {
    return response()->json([
        'session_data' => [
            'social_auth_game' => session('social_auth_game'),
            'intended_url' => session('url.intended'),
        ],
        'request_data' => [
            'query_params' => request()->query(),
            'full_url' => request()->fullUrl(),
        ],
    ]);
})->name('debug.social-auth');
