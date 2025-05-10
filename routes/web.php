<?php

use App\Livewire\Home;
use Livewire\Volt\Volt;
use App\Livewire\TeamPage;
use App\Livewire\CreateGame;
use App\Livewire\PlayerPage;
use Illuminate\Http\Request;
use App\Livewire\PreGameLobby;
use App\Livewire\GameDashboard;
use Illuminate\Support\Facades\Route;
use App\Livewire\GameTemplatesListPage;
use App\Livewire\ManageGameTemplatePage;
use Illuminate\Support\Facades\Redirect;

Route::get('/', function () {
    return view('welcome');
})->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Route::get('/dashboard', Home::class)->name('dashboard');
    Route::get('/create-game', CreateGame::class)->name('create-game');

    Route::missing(function (Request $request) {
        return Redirect::route('dashboard');
    })->group(function () {
        Route::get('/games/{game}/pre-game-lobby', PreGameLobby::class)->name('pre-game-lobby');
        Route::get('/games/{game}/dashboard', GameDashboard::class)->name('game-dashboard');
        Route::get('/games/{game}/teams/{team}', TeamPage::class)->name('teams.show');
        Route::get('/games/{game}/players/{player}', PlayerPage::class)->name('players.show');
    });

    Route::get('/game-templates', GameTemplatesListPage::class)->name('game-templates.index');
    Route::get('/game-templates/create', ManageGameTemplatePage::class)->name('game-templates.create');
    Route::get('/game-templates/{game_template}', ManageGameTemplatePage::class)->name('game-templates.show');
});

require __DIR__.'/auth.php';
