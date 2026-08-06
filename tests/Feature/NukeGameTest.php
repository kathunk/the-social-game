<?php

use App\Challenges\Laracon2025\FlattenTheCurve;
use App\Challenges\PeckingOrder\IndividualLowScoreQuiz;
use App\Livewire\PreGameLobby;
use App\Models\Challenge;
use App\Models\Game;
use App\Models\Modifier;
use App\Models\Player;
use App\Models\Team;
use App\Modifiers\PeckingOrder\BloodOaths;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('lets a game admin nuke an active individual game and deletes everything attached', function () {
    Verbs::commitImmediately();

    $this->mockGameTemplate(
        challenges: [['challenge_keys' => [IndividualLowScoreQuiz::key()], 'duration' => 10]],
        type: 'individual',
        modifiers: [BloodOaths::key()],
    );

    $this->createGame();
    $player = $this->createPlayer();
    $this->createPlayer();
    $this->game->start();

    $game_id = $this->game->id;
    $admin = $this->game->admins->first();

    expect(Player::where('game_id', $game_id)->count())->toBeGreaterThan(0);
    expect(Challenge::where('game_id', $game_id)->count())->toBeGreaterThan(0);
    expect($player->user->fresh()->current_game_id)->toBe($game_id);

    $this->actingAs($admin);

    Livewire::test(PreGameLobby::class, ['game' => $this->game->fresh()])
        ->call('nukeGame')
        ->assertRedirect(route('dashboard'));

    expect(Game::find($game_id))->toBeNull();
    expect(Player::where('game_id', $game_id)->count())->toBe(0);
    expect(Challenge::where('game_id', $game_id)->count())->toBe(0);
    expect(Modifier::where('game_id', $game_id)->count())->toBe(0);
    expect(DB::table('game_applications')->where('game_id', $game_id)->count())->toBe(0);
    expect(DB::table('game_admins')->where('game_id', $game_id)->count())->toBe(0);

    // Every user is detached from the vanished game
    expect($player->user->fresh()->current_game_id)->toBeNull();
    expect($player->user->fresh()->current_player_id)->toBeNull();

    // And their next visit lands on the dashboard, not an error
    $this->actingAs($player->user)
        ->get(route('game-dashboard', ['game' => $game_id]))
        ->assertRedirect(route('dashboard'));
});

it('nukes a team game in progress including its teams', function () {
    Verbs::commitImmediately();

    $this->mockGameTemplate(
        challenges: [['challenge_keys' => [FlattenTheCurve::key()], 'duration' => 10]],
        type: 'team',
        team_names: ['Red', 'Blue'],
    );

    $this->createGame();
    $this->createPlayer();
    $this->createPlayer();
    $this->game->start();

    $game_id = $this->game->id;
    expect(Team::where('game_id', $game_id)->count())->toBe(2);

    $this->actingAs($this->game->admins->first());

    Livewire::test(PreGameLobby::class, ['game' => $this->game->fresh()])
        ->call('nukeGame')
        ->assertRedirect(route('dashboard'));

    expect(Game::find($game_id))->toBeNull();
    expect(Team::where('game_id', $game_id)->count())->toBe(0);
});

it('does not let a non-admin player nuke the game', function () {
    Verbs::commitImmediately();

    $this->mockGameTemplate(
        challenges: [['challenge_keys' => [IndividualLowScoreQuiz::key()], 'duration' => 10]],
        type: 'individual',
    );

    $this->createGame();
    $player = $this->createPlayer();
    $this->game->start();

    $this->actingAs($player->user);

    Livewire::test(PreGameLobby::class, ['game' => $this->game->fresh()])
        ->call('nukeGame')
        ->assertForbidden();

    expect(Game::find($this->game->id))->not->toBeNull();
});
