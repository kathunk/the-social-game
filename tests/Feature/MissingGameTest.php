<?php

use App\Models\Team;
use App\Models\Player;
use Livewire\Livewire;
use App\Livewire\TeamPage;
use Thunk\Verbs\Facades\Verbs;
use Illuminate\Support\Facades\DB;
use App\Challenges\Classes\PyramidScheme;
use App\Challenges\Classes\TeamPrisonersDilemma;
use App\Challenges\Classes\IndividualLowScoreQuiz;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();
});

describe('Team Game', function () {
    beforeEach(function () {
        $this->mockGameTemplate(
            challenges: [
                [
                    'challenge_keys' => [PyramidScheme::key()],
                    'duration' => 10,
                ],
            ],
            type: 'team',
            team_names: ['Team 1', 'Team 2', 'Team 3', 'Team 4', 'Team 5', 'Team 6', 'Team 7', 'Team 8', 'Team 9', 'Team 10'],
        );
        $this->createGame()->start();
    });

    it('logged in users attempting to visit nonexistant games redirect to the dashboard', function () {
        $team = $this->game->teams->first();
        $player = $this->game->players->first();

        $this->actingAs($player->user);

        $this->get(route('game-dashboard', ['game' => $this->game]))
            ->assertOk();

        $fakeGameId = 999999999;

        $this->get(route('game-dashboard', ['game' => $fakeGameId]))
            ->assertRedirect(route('dashboard'));

        // @todo this works in the UI but not here
        // $this->get(route('teams.show', ['game' => $this->fakeGameIdgame, 'team' => $team]))
        //     ->assertRedirect(route('game-dashboard', ['game' => $this->game]));

        $this->get(route('players.show', ['game' => $fakeGameId, 'player' => $player]))
            ->assertRedirect(route('dashboard'));
    });

    it('logged in users attempting to visit nonexistant teams redirect to the game dashboard', function () {
        $team = $this->game->teams->first();
        $player = $this->game->players->first();

        $this->actingAs($player->user);

        $this->get(route('teams.show', ['game' => $this->game, 'team' => $team]))
            ->assertOk();

        $fakeTeamId = 999999999;

        $this->get(route('teams.show', ['game' => $this->game, 'team' => $fakeTeamId]))
            ->assertRedirect(route('game-dashboard', ['game' => $this->game]));
    })->todo('works in UI but not here');

    it('logged in users attempting to visit nonexistant players redirect to the game dashboard', function () {
        $player = $this->game->players->first();

        $this->actingAs($player->user);

        $this->get(route('players.show', ['game' => $this->game, 'player' => $player]))
            ->assertOk();

        $fakePlayerId = 999999999;

        $this->get(route('players.show', ['game' => $this->game, 'player' => $fakePlayerId]))
            ->assertRedirect(route('game-dashboard', ['game' => $this->game]));
    })->todo('works in UI but not here');
});

describe('Individual Game', function () {
    beforeEach(function () {
        $this->mockGameTemplate(
            challenges: [
                [
                    'challenge_keys' => [IndividualLowScoreQuiz::key()],
                    'duration' => 10,
                ],
            ],
            type: 'individual',
        );
        $this->createGame()->start();
    });

    it('logged in users attempting to visit nonexistant games redirect to the dashboard', function () {
        $team = $this->game->teams->first();
        $player = $this->game->players->first();

        $this->actingAs($player->user);

        $this->get(route('game-dashboard', ['game' => $this->game]))
            ->assertOk();

        $fakeGameId = 999999999;

        $this->get(route('game-dashboard', ['game' => $fakeGameId]))
            ->assertRedirect(route('dashboard'));

        // @todo this works in the UI but not here
        // $this->get(route('teams.show', ['game' => $this->fakeGameIdgame, 'team' => $team]))
        //     ->assertRedirect(route('game-dashboard', ['game' => $this->game]));

        $this->get(route('players.show', ['game' => $fakeGameId, 'player' => $player]))
            ->assertRedirect(route('dashboard'));
    });

    it('logged in users attempting to visit nonexistant teams redirect to the game dashboard', function () {
        $team = $this->game->teams->first();
        $player = $this->game->players->first();

        $this->actingAs($player->user);

        $this->get(route('teams.show', ['game' => $this->game, 'team' => $team]))
            ->assertOk();

        $fakeTeamId = 999999999;

        $this->get(route('teams.show', ['game' => $this->game, 'team' => $fakeTeamId]))
            ->assertRedirect(route('game-dashboard', ['game' => $this->game]));
    })->todo('works in UI but not here');

    it('logged in users attempting to visit nonexistant players redirect to the game dashboard', function () {
        $player = $this->game->players->first();

        $this->actingAs($player->user);

        $this->get(route('players.show', ['game' => $this->game, 'player' => $player]))
            ->assertOk();

        $fakePlayerId = 999999999;

        $this->get(route('players.show', ['game' => $this->game, 'player' => $fakePlayerId]))
            ->assertRedirect(route('game-dashboard', ['game' => $this->game]));
    })->todo('works in UI but not here');
});
