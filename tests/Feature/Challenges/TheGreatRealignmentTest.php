<?php

use App\Challenges\Classes\TeamFiller;
use App\Challenges\Classes\TheGreatRealignment;
use App\Livewire\GameDashboard;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs the Flatten the Curve challenge', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [TeamFiller::key()],
            'duration' => 10,
        ],
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
    incrementScore(10, $team, is_hidden: true);
    incrementScore(-100, $team_2);
    incrementScore(-10, $team_2, is_hidden: true);

    $player_1 = $this->createPlayer()->joinTeam($team);
    $player_2 = $this->createPlayer()->joinTeam($team);
    $player_3 = $this->createPlayer()->joinTeam($team_2);
    $player_4 = $this->createPlayer()->joinTeam($team_3);
    $player_5 = $this->createPlayer()->joinTeam($team_3);

    $first_challenge = $this->game->fresh()->currentChallenge;
    $first_challenge->end();
    $first_challenge->next()->start();
    $realigned_challenge = $this->game->fresh()->currentChallenge;

    $scoreboard = $realigned_challenge->challenge_data['previous_scoreboard'];
    $scoreboardArray = array_values($scoreboard);
    expect(count($scoreboardArray))->toBe(10);
    expect($scoreboardArray[0]['Name'])->toBe($team->name);
    expect($scoreboardArray[0]['Score'])->toBe(100);
    expect($scoreboardArray[0]['Players'])->toBe(2);

    expect($scoreboardArray[9]['Name'])->toBe($team_2->name);
    expect($scoreboardArray[9]['Score'])->toBe(-100);
    expect($scoreboardArray[9]['Players'])->toBe(1);

    expect($team->fresh()->score)->toBe(100);
    expect($team->fresh()->hidden_score)->toBe(110);
    expect($team_2->fresh()->score)->toBe(-100);
    expect($team_2->fresh()->hidden_score)->toBe(-110);

    Livewire::actingAs($player_1->user)
        ->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('Currently you carry 50 points and 5 hidden points')
        ->set('round_properties.'.TheGreatRealignment::key().'.team_id', $team_2->id)
        ->call('callClassAction', 'swapTeams', 'challenge', TheGreatRealignment::key())->assertHasNoErrors();

    expect($team->fresh()->score)->toBe(50);
    expect($team->fresh()->hidden_score)->toBe(55);
    expect($team_2->fresh()->score)->toBe(-50);
    expect($team_2->fresh()->hidden_score)->toBe(-55);

    Livewire::actingAs($player_3->user)
        ->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('Currently you carry -25 points and -3 hidden points')
        ->set('round_properties.'.TheGreatRealignment::key().'.team_id', $team_3->id)
        ->call('callClassAction', 'swapTeams', 'challenge', TheGreatRealignment::key())->assertHasNoErrors();

    expect($team_2->fresh()->score)->toBe(-25);
    expect($team_2->fresh()->hidden_score)->toBe(-27);
    expect($team_3->fresh()->score)->toBe(-25);
    expect($team_3->fresh()->hidden_score)->toBe(-28);
});
