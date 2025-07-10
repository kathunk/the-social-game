<?php

use App\Challenges\Classes\TeamFiller;
use App\Livewire\SecretsPage;
use App\Modifiers\Classes\TeamSecretCodes;
use Illuminate\Support\Facades\Date;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('handles secret codes in team games', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [TeamFiller::key()],
            'duration' => 10,
        ],
    ];

    $modifiers = [
        TeamSecretCodes::key(),
    ];

    $this->mockGameTemplate(challenges: $challenges, type: 'team', modifiers: $modifiers, team_names: ['team1', 'team2', 'team3', 'team4']);

    $this->createGame()->start();

    $mod = $this->game->modifiers->first();

    

    $team = $this->game->teams->first();

    $player_1 = $this->createPlayer();
    $player_1->joinTeam($team);
    $this->actingAs($player_1->user);


});
