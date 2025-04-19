<?php

use App\Models\User;
use Livewire\Volt\Volt;
use App\Livewire\TeamPage;
use App\Livewire\Dashboard;
use App\Livewire\PreGameLobby;
use App\Livewire\AdminDashboard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

if (config('auth.auto.login')
    && app()->environment('local')
    && ! Auth::check()
) {
    Auth::login(
        User::firstWhere('name', config('auth.auto.name'))
    );
}

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Route::get('/pre-game-lobby', PreGameLobby::class)->name('pre-game-lobby');
    Route::get('/admin-dashboard', AdminDashboard::class)->name('admin-dashboard');
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/teams/{team}', TeamPage::class)->name('teams.show');
});

require __DIR__.'/auth.php';
