<?php

use App\Livewire\Home;
use Livewire\Volt\Volt;
use App\Livewire\TeamPage;
use App\Livewire\Subscribe;
use App\Livewire\CreateGame;
use App\Livewire\PlayerPage;
use App\Livewire\SecretsPage;
use App\Livewire\PreGameLobby;
use App\Livewire\GameDashboard;
use App\Livewire\MarketingPage;
use App\Http\MissingGameHandler;
use App\Livewire\GameModesListPage;
use App\Livewire\ManageGameModePage;
use Illuminate\Support\Facades\Route;
use App\Livewire\GameComponentListPage;
use App\Livewire\ManageGameTemplatePage;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\CheckoutController;

Route::get('/', function () {
    return view('welcome');
})->name('dashboard');

Route::missing(new MissingGameHandler)->group(function () {
    Route::get('/games/{game}/pre-game-lobby', PreGameLobby::class)->name('pre-game-lobby');
});

Route::get('/marketing-page', MarketingPage::class)->name('marketing-page');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

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
    Route::get('/games/{game}/secrets/{modifier}', SecretsPage::class)->name('games.secrets');

    Route::prefix('subscribe')->name('subscribe.')->group(function () {
        Route::get('/', Subscribe::class)->name('index');

        Route::get('success', [CheckoutController::class, 'success'])->name('success');
        Route::get('cancel', [CheckoutController::class, 'cancel'])->name('cancel');
    });
});

Route::post('stripe/webhook',[StripeWebhookController::class, 'handleWebhook'])->name('cashier.webhook')
    ->withoutMiddleware(['web', 'auth']);

require __DIR__.'/auth.php';
