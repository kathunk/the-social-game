<?php

use App\Challenges\Classes\FlattenTheCurve;
use Illuminate\Support\Facades\Date;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs the Flatten the Curve challenge', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [FlattenTheCurve::key()],
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

    // new player joins and scores 1 point
    $player_1 = $this->createPlayer()->joinTeam($team);
    $player_2 = $this->createPlayer()->joinTeam($team);
    $player_2->fresh()->joinTeam($team_2);
    $player_3 = $this->createPlayer()->joinTeam($team_2);
    $player_4 = $this->createPlayer()->joinTeam($team_3);
    $player_5 = $this->createPlayer()->joinTeam($team_3);
    $player_6 = $this->createPlayer()->joinTeam($team_3);
    $player_7 = $this->createPlayer()->joinTeam($team_3);
    $player_8 = $this->createPlayer()->joinTeam($team_3);
    $player_9 = $this->createPlayer()->joinTeam($team_3);
    $player_10 = $this->createPlayer()->joinTeam($team_3);

    expect($team->fresh()->score)->toBe(0);
    expect($team->fresh()->players->count())->toBe(1);
    expect($team_2->fresh()->score)->toBe(0);
    expect($team_2->fresh()->players->count())->toBe(2);
    expect($team_3->fresh()->score)->toBe(0);
    expect($team_3->fresh()->players->count())->toBe(7);

    // challenge ends and the first place team loses all its points
    $end = $this->game->fresh()->currentChallenge->ends_at;
    Date::setTestNow($end->addSeconds(1));
    $this->artisan('app:progress-games');

    // average team size is 1
    // first team has 1 player
    expect($team->fresh()->score)->toBe(0);

    // second team has 2 players
    expect($team_2->fresh()->score)->toBe(-5);

    // third team has 7 players
    expect($team_3->fresh()->score)->toBe(-30);

    // fourth team has 0 players
    expect($team_4->fresh()->score)->toBe(5);
});
