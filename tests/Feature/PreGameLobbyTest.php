<?php

use App\Models\Game;
use App\Models\User;
use Livewire\Livewire;
use App\Models\GameMode;
use App\Livewire\CreateGame;
use App\Models\GameTemplate;
use App\Livewire\PreGameLobby;
use Thunk\Verbs\Facades\Verbs;
use App\Events\UserGainedMembership;
use App\Modifiers\Classes\BloodOaths;
use App\Challenges\Classes\TeamHotPotato;
use App\Modifiers\Classes\TeamResignation;
use App\Modifiers\Classes\TeamSecretCodes;
use App\Challenges\Classes\FlattenTheCurve;
use App\Challenges\Classes\TeamBrinksmanship;
use App\Challenges\Classes\TeamPrisonersDilemma;
use App\Challenges\Classes\IndividualLowScoreQuiz;
use App\Challenges\Classes\IndividualHighScoreQuiz;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            "challenge_keys" => [IndividualLowScoreQuiz::key()],
            "duration" => 10,
        ],
        [
            "challenge_keys" => [IndividualHighScoreQuiz::key()],
            "duration" => 10,
        ],
    ];

    $this->mockGameTemplate(challenges: $challenges, type: "individual", modifiers: [BloodOaths::key()]);

    $challenges = [
        [
            "challenge_keys" => [FlattenTheCurve::key()],
            "duration" => 100,
        ],
        [
            "challenge_keys" => [TeamHotPotato::key()],
            "duration" => 100,
        ],
    ];

    $this->mockGameTemplate(challenges: $challenges, type: "team", modifiers: [TeamSecretCodes::key()]);

    $this->individual_template = GameTemplate::where(
        "type",
        "individual"
    )->first();
    $this->team_template = GameTemplate::where("type", "team")->first();
    $this->individual_mode = GameMode::where("type", "individual")->first();
    $this->team_mode = GameMode::where("type", "team")->first();

    $this->john = User::fromTemplate(
        "John Drexler",
        "john@example.com",
        "password"
    );

    UserGainedMembership::fire(user_id: $this->john->id);

    $this->actingAs($this->john);
});

it("can create a game", function () {
    Livewire::test(CreateGame::class)
        ->set("game_mode_id", $this->individual_mode->id)
        ->call("createGame");

    $this->assertDatabaseHas("games", [
        "game_mode_id" => $this->individual_mode->id,
        "game_template_id" => $this->individual_template->id,
        "status" => "upcoming",
    ]);
});

it("creates modifier configurations before the game starts", function () {
    Livewire::test(CreateGame::class)
        ->set("game_mode_id", $this->team_mode->id)
        ->call("createGame");

    $game = Game::first();

    $modifier_configuration = $game->modifierConfigurations->first();

    expect($modifier_configuration->modifier_key)->toBe(TeamSecretCodes::key());
    expect($modifier_configuration->modifier_data)->toBe(TeamSecretCodes::defaultDataForPreGameConfiguration());

    Livewire::test(PreGameLobby::class, ['game' => $game])
        ->set("game_mode_id", $this->individual_mode->id)
        ->call("updateGameSettings");

    expect($game->fresh()->modifierConfigurations->count())->toBe(0);

    Livewire::test(PreGameLobby::class, ['game' => $game])
        ->set("game_mode_id", $this->team_mode->id)
        ->call("updateGameSettings");

    $modifier_configuration = $game->fresh()->modifierConfigurations->first();

    expect($modifier_configuration->modifier_key)->toBe(TeamSecretCodes::key());
    expect($modifier_configuration->modifier_data)->toBe(TeamSecretCodes::defaultDataForPreGameConfiguration());
});