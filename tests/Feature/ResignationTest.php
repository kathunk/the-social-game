<?php

use App\Challenges\Classes\PyramidScheme;
use App\Livewire\GameDashboard;
use App\Modifiers\Classes\TeamResignation;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('allows for resignations in team games', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [PyramidScheme::key()],
            'duration' => 10,
        ],
    ];

    $modifiers = [TeamResignation::key()];

    $this->mockGameTemplate(challenges: $challenges, type: 'team', modifiers: $modifiers, team_names: ['team1', 'team2', 'team3', 'team4']);

    $this->createGame()->start();

    $team = $this->game->teams->first();

    $player_1 = $this->createPlayer();

    $this->actingAs($player_1->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game])
        ->assertDontSee('Had enough?');

    $player_1->joinTeam($team);
    $player_1->refresh();

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('Had enough?')
        ->call('callModifierAction', TeamResignation::key(), 'resign', ['points' => '-3']);
});
