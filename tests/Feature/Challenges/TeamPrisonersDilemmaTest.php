<?php

use App\Challenges\Classes\Laracon2025\TeamPrisonersDilemma;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use Livewire\Features\SupportTesting\Testable as LivewireTest;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
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

/* cannot redeclare "playDirty" method, so appending LW */
function playDirtyLW($player): LivewireTest
{
    return Livewire::actingAs($player->user)
        ->test(GameDashboard::class, ['game' => $player->game->fresh()])
        ->call('callClassAction', 'playDirty', 'challenge', TeamPrisonersDilemma::key())->assertHasNoErrors();
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

it('cannot be used with an odd number of teams', function () {
    $challenges = [
        [
            'challenge_keys' => [TeamPrisonersDilemma::key()],
            'duration' => 10,
        ],
    ];

    expect(function () use ($challenges) {
        $this->mockGameTemplate(
            challenges: $challenges,
            type: 'team',
            team_names: ['Team 1', 'Team 2', 'Team 3'],
        );
    })->toThrow(Exception::class, 'Prisoner\'s Dilemma requires an even number of teams.');
});

it('can handle a verbs replay', function () {
    Verbs::commit();

    $challenge_data = $this->game->fresh()->currentChallenge->challenge_data;

    $this->artisan('db:reset-data');
    $this->artisan('verbs:replay');

    $new_challenge_data = $this->game->fresh()->currentChallenge->challenge_data;

    expect($new_challenge_data)->toBe($challenge_data);
});
