<?php

use App\Challenges\ElephantInTheRoom\ElephantMatch;
use App\Events\GameModeAdded;
use App\Events\GameTemplateAdded;
use App\Livewire\Home;
use App\Models\Game;
use App\Models\GameMode;
use App\Modifiers\ElephantInTheRoom\ElephantRematch;
use App\Support\GameModeCardRegistry;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Seeds the two elephant modes the way the real seeder does (public so a
 * regular user can see and start them). Returns [versus_mode, bot_mode].
 */
function seedElephantModes($test): array
{
    Verbs::commitImmediately();

    $versus_id = GameModeAdded::fire(
        name: 'Elephant in the Room',
        description: 'Head to head.',
        pre_game_lobby_message: 'welcome',
        type: 'individual',
        min_players: 2,
        max_players: 2,
        is_public: true,
        players_can_join_late: false,
        scoreboard_type: 'none',
    )->game_mode_id;

    GameTemplateAdded::fire(
        game_mode_id: $versus_id,
        name: 'Elephant Template',
        type: 'individual',
        is_public: true,
        team_names: [],
        challenges: [['challenge_keys' => [ElephantMatch::key()], 'duration' => 20]],
        modifiers: [ElephantRematch::key()],
    );

    $bot_id = GameModeAdded::fire(
        name: 'Elephant in the Room (vs Bot)',
        description: 'Practice.',
        pre_game_lobby_message: 'welcome',
        type: 'individual',
        min_players: 1,
        max_players: 1,
        is_public: true,
        players_can_join_late: false,
        scoreboard_type: 'none',
        skips_pre_game_lobby: true,
    )->game_mode_id;

    GameTemplateAdded::fire(
        game_mode_id: $bot_id,
        name: 'Elephant vs Bot Template',
        type: 'individual',
        is_public: true,
        team_names: [],
        challenges: [['challenge_keys' => [ElephantMatch::key()], 'duration' => 20]],
        modifiers: [ElephantRematch::key()],
    );

    return [GameMode::find($versus_id), GameMode::find($bot_id)];
}

it('groups both elephant modes into a single elephant card', function () {
    seedElephantModes($this);

    $cards = GameModeCardRegistry::groupForDisplay(GameMode::all());
    $elephant_cards = $cards->where('component', 'game-mode-cards.elephant');

    expect($elephant_cards)->toHaveCount(1);
    expect($elephant_cards->first()['modes'])->toHaveCount(2);
});

it('shows the elephant card with both options on the home page', function () {
    seedElephantModes($this);

    $user = $this->createUser('Card Viewer', 'card-viewer@example.com', 'password');

    $this->actingAs($user);

    Livewire::test(Home::class)
        ->assertSee('Elephant in the Room')
        ->assertSee('Challenge a friend')
        ->assertSee('Practice vs the Bot');
});

it('sends "challenge a friend" through the pre-game lobby as usual', function () {
    [$versus] = seedElephantModes($this);

    $user = $this->createUser('Challenger', 'challenger@example.com', 'password');

    $this->actingAs($user);

    Livewire::test(Home::class)
        ->call('startGameFromMode', (string) $versus->id)
        ->assertRedirect(route('pre-game-lobby', Game::first()));

    expect(Game::first()->status)->toBe('upcoming');
});

it('starts any single-player mode instantly even without the flag', function () {
    [, $bot] = seedElephantModes($this);

    // A max_players 1 mode never needs a lobby — the instant start must not
    // depend on skips_pre_game_lobby being remembered (e.g. modes created in
    // prod via the admin UI before the flag existed)
    GameMode::find($bot->id)->update(['skips_pre_game_lobby' => false]);

    $user = $this->createUser('Flagless', 'flagless@example.com', 'password');

    $this->actingAs($user);

    Livewire::test(Home::class)
        ->call('startGameFromMode', (string) $bot->id)
        ->assertRedirect(route('game-dashboard', ['game' => Game::first()->id]));

    expect(Game::first()->status)->toBe('active');
});

it('starts a bot game instantly, skipping the lobby', function () {
    [, $bot] = seedElephantModes($this);

    $user = $this->createUser('Soloist', 'soloist@example.com', 'password');

    $this->actingAs($user);

    Livewire::test(Home::class)
        ->call('startGameFromMode', (string) $bot->id)
        ->assertRedirect(route('game-dashboard', ['game' => Game::first()->id]));

    $game = Game::first();

    expect($game->status)->toBe('active');
    expect($game->players)->toHaveCount(1);

    $match = $game->challenges->firstWhere('class_key', ElephantMatch::key());

    expect($match->status)->toBe('active');
    expect($match->challenge_data['is_bot_game'])->toBeTrue();
});

it('falls back to the lobby when an instant-start mode lacks its minimum players', function () {
    [$versus] = seedElephantModes($this);

    // Misconfiguration: instant-start flag on a 2-player mode. The creator
    // alone can't satisfy min_players, so the guard sends them to the lobby.
    GameMode::find($versus->id)->update(['skips_pre_game_lobby' => true]);

    $user = $this->createUser('Impatient', 'impatient@example.com', 'password');

    $this->actingAs($user);

    Livewire::test(Home::class)
        ->call('startGameFromMode', (string) $versus->id)
        ->assertRedirect(route('pre-game-lobby', Game::first()));

    expect(Game::first()->status)->toBe('upcoming');
});
