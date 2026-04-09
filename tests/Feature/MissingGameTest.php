<?php

use App\Challenges\Classes\PeckingOrder\IndividualLowScoreQuiz;
use App\Challenges\Classes\PeckingOrder\PyramidScheme;
use Thunk\Verbs\Facades\Verbs;

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

        $this->team = $this->game->teams->first();
        $this->player = $this->game->players->first();

        $this->actingAs($this->player->user);
    });

    it('logged in users attempting to visit nonexistant games redirect to the dashboard', function () {
        $this->get(route('game-dashboard', ['game' => $this->game]))
            ->assertOk();

        $fakeGameId = 999999999;

        $this->get(route('game-dashboard', ['game' => $fakeGameId]))
            ->assertRedirect(route('dashboard'));

        $this->get(route('teams.show', ['game' => $fakeGameId, 'team' => $this->team]))
            ->assertRedirect(route('dashboard'));

        $this->get(route('players.show', ['game' => $fakeGameId, 'player' => $this->player]))
            ->assertRedirect(route('dashboard'));
    });

    it('logged in users attempting to visit nonexistant teams redirect to the game dashboard', function () {
        $this->get(route('teams.show', ['game' => $this->game, 'team' => $this->team]))
            ->assertOk();

        $fakeTeamId = 999999999;

        $this->get(route('teams.show', ['game' => $this->game, 'team' => $fakeTeamId]))
            ->assertRedirect(route('game-dashboard', ['game' => $this->game]));
    });

    it('logged in users attempting to visit nonexistant players redirect to the game dashboard', function () {
        $this->get(route('players.show', ['game' => $this->game, 'player' => $this->player]))
            ->assertOk();

        $fakePlayerId = 999999999;

        $this->get(route('players.show', ['game' => $this->game, 'player' => $fakePlayerId]))
            ->assertRedirect(route('game-dashboard', ['game' => $this->game]));
    });
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
            team_names: ['Team 1', 'Team 2', 'Team 3', 'Team 4', 'Team 5', 'Team 6', 'Team 7', 'Team 8', 'Team 9', 'Team 10'],
        );
        $this->createGame()->start();

        $this->player = $this->game->players->first();

        $this->team = $this->game->teams->first();

        $this->actingAs($this->player->user);
    });

    it('logged in users attempting to visit nonexistant games redirect to the dashboard', function () {
        $this->get(route('game-dashboard', ['game' => $this->game]))
            ->assertOk();

        $fakeGameId = 999999999;

        $this->get(route('game-dashboard', ['game' => $fakeGameId]))
            ->assertRedirect(route('dashboard'));

        $this->get(route('teams.show', ['game' => $fakeGameId, 'team' => $this->team]))
            ->assertRedirect(route('dashboard'));

        $this->get(route('players.show', ['game' => $fakeGameId, 'player' => $this->player]))
            ->assertRedirect(route('dashboard'));
    });

    it('logged in users attempting to visit nonexistant teams redirect to the game dashboard', function () {
        $this->get(route('teams.show', ['game' => $this->game, 'team' => $this->team]))
            ->assertOk();

        $fakeTeamId = 999999999;

        $this->get(route('teams.show', ['game' => $this->game, 'team' => $fakeTeamId]))
            ->assertRedirect(route('game-dashboard', ['game' => $this->game]));
    });

    it('logged in users attempting to visit nonexistant players redirect to the game dashboard', function () {
        $this->get(route('players.show', ['game' => $this->game, 'player' => $this->player]))
            ->assertOk();

        $fakePlayerId = 999999999;

        $this->get(route('players.show', ['game' => $this->game, 'player' => $fakePlayerId]))
            ->assertRedirect(route('game-dashboard', ['game' => $this->game]));
    });
});
