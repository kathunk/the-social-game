<?php

use App\Challenges\Classes\TeamPopularityContest;
use App\Livewire\GameDashboard;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::fake();
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [TeamPopularityContest::key()],
            'duration' => 10,
        ],
    ];

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'team',
        team_names: ['Team 1', 'Team 2', 'Team 3', 'Team 4'],
        modifiers: [],
    );

    $this->createGame()->start();
});

it('runs the Team Recruiter modifier', function () {
    $team = $this->game->teams->first();
    $team_2 = $this->game->teams->skip(1)->first();
    $team_3 = $this->game->teams->skip(2)->first();

    // new players join and scores 2 points
    $player_1 = $this->createPlayer();
    $player_2 = $this->createPlayer();
    $player_3 = $this->createPlayer();

    $player_1->joinTeam($team);
    $player_2->joinTeam($team_2);
    $player_3->joinTeam($team_2);

    Livewire::actingAs($player_1->user)
        ->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.TeamPopularityContest::key().'.upvote_team_id', $team_3->id)
        ->set('round_properties.'.TeamPopularityContest::key().'.downvote_team_id', $team_2->id)
        ->call('callClassAction', 'vote', 'challenge', TeamPopularityContest::key())
        ->assertHasNoErrors();

    Livewire::actingAs($player_1->user)
        ->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('🗳️ Upvoted Team 3 and downvoted Team 2.');

    Livewire::actingAs($player_2->user)
        ->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.TeamPopularityContest::key().'.upvote_team_id', $team->id)
        ->set('round_properties.'.TeamPopularityContest::key().'.downvote_team_id', $team_3->id)
        ->call('callClassAction', 'vote', 'challenge', TeamPopularityContest::key())
        ->assertHasNoErrors();

    $this->game->fresh()->currentChallenge->end();

    expect($team->fresh()->score)->toBe(1);
    expect($team_2->fresh()->score)->toBe(-1);
    expect($team_3->fresh()->score)->toBe(0);
});
