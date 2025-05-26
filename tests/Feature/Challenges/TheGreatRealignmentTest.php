<?php

use App\Challenges\Classes\TheGreatRealignment;
use App\Livewire\GameDashboard;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs the Flatten the Curve challenge', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [TheGreatRealignment::key()],
            'duration' => 10,
        ],
    ];

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'team',
        team_names: ['Team 1', 'Team 2', 'Team 3', 'Team 4', 'Team 5', 'Team 6', 'Team 7', 'Team 8', 'Team 9', 'Team 10'],
    );
    $this->createGame()->start();

    $team = $this->game->teams->first();
    $team_2 = $this->game->teams->skip(1)->first();
    $team_3 = $this->game->teams->skip(2)->first();
    $team_4 = $this->game->teams->skip(3)->first();

    incrementScore(100, $team);
    incrementScore(-100, $team_2);

    $player_1 = $this->createPlayer()->joinTeam($team);
    $player_2 = $this->createPlayer()->joinTeam($team);
    $player_3 = $this->createPlayer()->joinTeam($team_2);
    $player_4 = $this->createPlayer()->joinTeam($team_3);
    $player_5 = $this->createPlayer()->joinTeam($team_3);

    Livewire::actingAs($player_1->user)
        ->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('Currently you carry 50 points')
        ->set('round_properties.'.TheGreatRealignment::key().'.team_id', $team_2->id)
        ->call('callClassAction', 'swapTeams', 'challenge', TheGreatRealignment::key())->assertHasNoErrors();

    expect($team->fresh()->score)->toBe(50);
    expect($team_2->fresh()->score)->toBe(-50);

    Livewire::actingAs($player_3->user)
        ->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('Currently you carry -25 points')
        ->set('round_properties.'.TheGreatRealignment::key().'.team_id', $team_3->id)
        ->call('callClassAction', 'swapTeams', 'challenge', TheGreatRealignment::key())->assertHasNoErrors();

    expect($team_2->fresh()->score)->toBe(-25);
    expect($team_3->fresh()->score)->toBe(-25);
});
