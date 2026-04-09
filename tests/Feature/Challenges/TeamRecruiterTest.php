<?php

use App\Challenges\Classes\Laracon2025\TeamWarmUp;
use App\Livewire\GameDashboard;
use App\Modifiers\Classes\Laracon2025\TeamRecruiter;
use Livewire\Features\SupportTesting\Testable as LivewireTest;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::fake();
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [TeamWarmUp::key()],
            'duration' => 10,
        ],
    ];

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'team',
        team_names: ['Team 1', 'Team 2', 'Team 3', 'Team 4'],
        modifiers: [TeamRecruiter::key()],
    );

    $this->createGame()->start();
});

function joinTeam($player, $team_id): LivewireTest
{
    return Livewire::actingAs($player->user)
        ->test(GameDashboard::class, ['game' => $player->game->fresh()])
        ->set('selected_team_id', $team_id)
        ->call('joinTeam', 'challenge', TeamWarmUp::key());
}

it('runs the Team Recruiter modifier', function () {
    $team = $this->game->teams->first();
    $team_2 = $this->game->teams->skip(1)->first();
    $team_3 = $this->game->teams->skip(2)->first();

    // new players join and scores 2 points
    $player_1 = $this->createPlayer();
    $player_2 = $this->createPlayer();
    $player_3 = $this->createPlayer();

    joinTeam($player_1, $team->id);
    joinTeam($player_2, $team_2->id);
    joinTeam($player_3, $team_2->id);

    expect($team->fresh()->score)->toBe(1);
    expect($team_2->fresh()->score)->toBe(2);
    expect($team_3->fresh()->score)->toBe(0);

    // player changes team, but score is not affected
    swapTeam($player_1->fresh(), $team_3->id, TeamWarmUp::key())
        ->assertHasNoErrors();

    expect($team->fresh()->score)->toBe(1);
    expect($team_2->fresh()->score)->toBe(2);
    expect($team_3->fresh()->score)->toBe(0);
});

describe('validate swapTeam', function () {
    it('team_id is required', function () {
        $player = $this->createPlayer();

        joinTeam($player->fresh(), $this->game->teams->first()->id);

        swapTeam($player->fresh(), '', TeamWarmUp::key())
            ->assertHasErrors(['round_properties.'.TeamWarmUp::key().'.team_id' => 'required']);
    });

    it('team_id must be a valid team', function () {
        $player = $this->createPlayer();

        joinTeam($player->fresh(), $this->game->teams->first()->id);

        swapTeam($player->fresh(), 999, TeamWarmUp::key())
            ->assertHasErrors(['round_properties.'.TeamWarmUp::key().'.team_id' => 'exists']);
    });
});

describe('validate joinTeam', function () {
    it('team_id is required', function () {
        $player = $this->createPlayer();

        joinTeam($player->fresh(), '', TeamWarmUp::key())
            ->assertHasErrors(['selected_team_id' => 'required']);
    });

    it('team_id must be a valid team', function () {
        $player = $this->createPlayer();

        joinTeam($player->fresh(), 999, TeamWarmUp::key())
            ->assertHasErrors(['selected_team_id' => 'exists']);
    });
});
