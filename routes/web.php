<?php

use Livewire\Volt\Volt;
use App\Livewire\PreGameLobby;
use App\Livewire\AdminDashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('dashboard', function () {
    if (auth()->user()->status === 'new') {
        return redirect()->route('pre-game-lobby');
    }
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Route::get('/pre-game-lobby', PreGameLobby::class)->name('pre-game-lobby');
    Route::get('/admin-dashboard', AdminDashboard::class)->name('admin-dashboard');
});

require __DIR__.'/auth.php';
