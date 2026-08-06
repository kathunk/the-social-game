<?php

use App\Http\Controllers\LaraconRedirectController;
use App\Http\Controllers\SecretAllianceRedirectController;
use App\Http\Controllers\SecretCodeRedirectController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\MissingGameHandler;
use App\Livewire\CreateGame;
use App\Livewire\GameComponentListPage;
use App\Livewire\GameDashboard;
use App\Livewire\GameModesListPage;
use App\Livewire\Home;
use App\Livewire\ManageGameModePage;
use App\Livewire\ManageGameTemplatePage;
use App\Livewire\ModifierConfigurationPage;
use App\Livewire\PlayerPage;
use App\Livewire\PreGameLobby;
use App\Livewire\SecretsPage;
use App\Livewire\TeamPage;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('dashboard');

Route::get('/laracon', [LaraconRedirectController::class, 'handle'])
    ->name('laracon.shortcut');

Route::missing(new MissingGameHandler)->group(function () {
    Route::get('/games/{game}/pre-game-lobby', PreGameLobby::class)->name('pre-game-lobby');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    // Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Route::get('/dashboard', Home::class)->name('dashboard');
    Route::get('/create-game', CreateGame::class)->name('create-game');

    Route::missing(new MissingGameHandler)->group(function () {
        Route::get('/games/{game}/dashboard', GameDashboard::class)->name('game-dashboard');
        Route::get('/games/{game}/teams/{team}', TeamPage::class)->name('teams.show');
        Route::get('/games/{game}/players/{player}', PlayerPage::class)->name('players.show');
    });

    Route::get('/game-components', GameComponentListPage::class)->name('game-components.index');

    Route::get('/game-modes', GameModesListPage::class)->name('game-modes.index');
    Route::get('/game-modes/create', ManageGameModePage::class)->name('game-modes.create');
    Route::get('/game-modes/{game_mode}', ManageGameModePage::class)->name('game-modes.show');
    Route::get('/{game_mode}/game-templates/create', ManageGameTemplatePage::class)->name('game-templates.create');
    Route::get('{game_mode}/game-templates/{game_template}', ManageGameTemplatePage::class)->name('game-templates.show');
    Route::get('/games/{game}/mods/{modifier}', SecretsPage::class)->name('games.mods');

    Route::get('/games/{game}/modifier-configurations', ModifierConfigurationPage::class)->name('games.modifier-configurations');
    Route::get('/secret-code', [SecretCodeRedirectController::class, 'handle'])
        ->name('secret-code.shortcut');
    Route::get('/secret-ally', [SecretAllianceRedirectController::class, 'handle'])
        ->name('secret-ally.shortcut');
});

Route::post('stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook')
    ->withoutMiddleware(['web', 'auth']);

require __DIR__.'/auth.php';
