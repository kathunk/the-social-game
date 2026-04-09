<?php

use App\Challenges\Laracon2025\FlattenTheCurve;
use App\Challenges\PeckingOrder\IndividualHighScoreQuiz;
use App\Challenges\PeckingOrder\IndividualLowScoreQuiz;
use App\Challenges\Laracon2025\TeamHotPotato;
use App\Events\UserGainedMembership;
use App\Livewire\CreateGame;
use App\Livewire\PreGameLobby;
use App\Models\Game;
use App\Models\GameMode;
use App\Models\GameTemplate;
use App\Models\User;
use App\Modifiers\PeckingOrder\BloodOaths;
use App\Modifiers\Laracon2025\TeamSecretCodes;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualLowScoreQuiz::key()],
            'duration' => 10,
        ],
        [
            'challenge_keys' => [IndividualHighScoreQuiz::key()],
            'duration' => 10,
        ],
    ];

    $this->mockGameTemplate(challenges: $challenges, type: 'individual', modifiers: [BloodOaths::key()]);

    $challenges = [
        [
            'challenge_keys' => [FlattenTheCurve::key()],
            'duration' => 100,
        ],
        [
            'challenge_keys' => [TeamHotPotato::key()],
            'duration' => 100,
        ],
    ];

    $this->mockGameTemplate(challenges: $challenges, type: 'team', modifiers: [TeamSecretCodes::key()]);

    $this->individual_template = GameTemplate::where(
        'type',
        'individual'
    )->first();
    $this->team_template = GameTemplate::where('type', 'team')->first();
    $this->individual_mode = GameMode::where('type', 'individual')->first();
    $this->team_mode = GameMode::where('type', 'team')->first();

    $this->john = User::fromTemplate(
        'John Drexler',
        'john@example.com',
        'password'
    );

    UserGainedMembership::fire(user_id: $this->john->id);

    $this->actingAs($this->john);
});

it('can create a game', function () {
    Livewire::test(CreateGame::class)
        ->set('game_mode_id', $this->individual_mode->id)
        ->call('createGame');

    $this->assertDatabaseHas('games', [
        'game_mode_id' => $this->individual_mode->id,
        'game_template_id' => $this->individual_template->id,
        'status' => 'upcoming',
    ]);
});

it('creates modifier configurations before the game starts', function () {
    Livewire::test(CreateGame::class)
        ->set('game_mode_id', $this->team_mode->id)
        ->call('createGame');

    $game = Game::first();

    $modifier_configuration = $game->modifierConfigurations->first();

    expect($modifier_configuration->modifier_key)->toBe(TeamSecretCodes::key());
    expect($modifier_configuration->modifier_data)->toBe(TeamSecretCodes::DEFAULT_CONFIGURATION);

    Livewire::test(PreGameLobby::class, ['game' => $game])
        ->set('game_mode_id', $this->individual_mode->id)
        ->call('updateGameSettings');

    expect($game->fresh()->modifierConfigurations->count())->toBe(0);

    Livewire::test(PreGameLobby::class, ['game' => $game])
        ->set('game_mode_id', $this->team_mode->id)
        ->call('updateGameSettings');

    $modifier_configuration = $game->fresh()->modifierConfigurations->first();

    expect($modifier_configuration->modifier_key)->toBe(TeamSecretCodes::key());
    expect($modifier_configuration->modifier_data)->toBe(TeamSecretCodes::DEFAULT_CONFIGURATION);
});
