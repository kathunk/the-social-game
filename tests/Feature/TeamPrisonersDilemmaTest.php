<?php

use App\Challenges\Classes\TeamPrisonersDilemma;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use Livewire\Features\SupportTesting\Testable as LivewireTest;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::fake();
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [TeamPrisonersDilemma::key()],
            'duration' => 10,
        ],
    ];

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'team',
        team_names: ['Team 1', 'Team 2', 'Team 3', 'Team 4'],
    );

    $this->createGame()->start();

    $this->team_1 = $this->game->teams->first();
    $this->team_2 = $this->game->teams->skip(1)->first();
    $this->team_3 = $this->game->teams->skip(2)->first();
    $this->team_4 = $this->game->teams->skip(3)->first();

    $this->player_1 = $this->createPlayer()->joinTeam($this->team_1);
    $this->player_2 = $this->createPlayer()->joinTeam($this->team_1);

    $this->player_3 = $this->createPlayer()->joinTeam($this->team_2);
    $this->player_4 = $this->createPlayer()->joinTeam($this->team_2);

    $this->player_5 = $this->createPlayer()->joinTeam($this->team_3);
    $this->player_6 = $this->createPlayer()->joinTeam($this->team_3);

    $this->player_7 = $this->createPlayer()->joinTeam($this->team_4);
    $this->player_8 = $this->createPlayer()->joinTeam($this->team_4);

    incrementScore(team: $this->team_1, points: 20);
    incrementScore(team: $this->team_2, points: 20);

    incrementScore(team: $this->team_3, points: 10);
    incrementScore(team: $this->team_4, points: 10);
});

function playDirtyLW($player): LivewireTest
{
    return Livewire::actingAs($player->user)
        ->test(GameDashboard::class, ['game' => $player->game->fresh()])
        ->call('callChallengeAction', 'playDirty');
}

it('if both teams play dirty they will each get -20 points', function () {
    playDirtyLW($this->player_1);
    playDirtyLW($this->player_3);

    Challenge::latest()->first()->end();

    expect($this->team_1->score)->toBe(0);
    expect($this->team_2->score)->toBe(0);
});

it('if your team play dirty and your paired_team does not you will get 50 points', function () {
    playDirtyLW($this->player_1);

    Challenge::latest()->first()->end();

    expect($this->team_1->fresh()->score)->toBe(70);
    expect($this->team_2->fresh()->score)->toBe(20);
});

it('if neither team plays dirty they will each get 20 points', function () {
    Challenge::latest()->first()->end();

    expect($this->team_1->fresh()->score)->toBe(40);
    expect($this->team_2->fresh()->score)->toBe(40);
    expect($this->team_3->fresh()->score)->toBe(30);
    expect($this->team_4->fresh()->score)->toBe(30);
});
