<?php

use App\Livewire\Home;
use Livewire\Volt\Volt;
use App\Livewire\TeamPage;
use App\Livewire\Dashboard;
use App\Livewire\CreateGame;
use App\Livewire\PreGameLobby;
use App\Livewire\AdminDashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Route::get('/games/{game}/pre-game-lobby', PreGameLobby::class)->name('pre-game-lobby');
    Route::get('/games/{game}/admin-dashboard', AdminDashboard::class)->name('admin-dashboard');
    Route::get('/games/{game}/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/home', Home::class)->name('home');
    Route::get('/teams/{team}', TeamPage::class)->name('teams.show');
    Route::get('/create-game', CreateGame::class)->name('create-game');
});

require __DIR__.'/auth.php';
