<?php

use App\Challenges\Classes\PyramidScheme;
use App\GameTemplates\TestTemplate;
use Illuminate\Support\Facades\Date;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs the Pyramid Scheme challenge', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [PyramidScheme::key()],
            'duration' => 10,
        ],
    ];

    $this->mockGameTemplate(challenges: $challenges, type: 'team');

    $this->createGame()->start();

    $team = $this->game->teams->first();
    $team_2 = $this->game->teams->skip(1)->first();
    $team_3 = $this->game->teams->skip(2)->first();

    // new player joins and scores 1 point
    $player_1 = $this->createPlayer()->joinTeam($team);
    $player_2 = $this->createPlayer()->joinTeam($team_2);
    $player_3 = $this->createPlayer()->joinTeam($team_2);

    expect($team->fresh()->score)->toBe(1);
    expect($team_2->fresh()->score)->toBe(2);
    expect($team_3->fresh()->score)->toBe(0);

    // player changes team, but score is not affected
    $player_1->fresh()->joinTeam($team_3);

    expect($team->fresh()->score)->toBe(1);
    expect($team_2->fresh()->score)->toBe(2);
    expect($team_3->fresh()->score)->toBe(0);

    // @todo need generalized logic for this
    // player is no longer allowed to change team
    // expect($player_1->fresh()->joinTeam($team_2))->toThrow(Exception::class);

    // challenge ends and the first place team loses all its points
    $end = $this->game->fresh()->currentChallenge->ends_at;
    Date::setTestNow($end->addSeconds(1));
    $this->artisan('app:progress-games');

    expect($team->fresh()->score)->toBe(1);
    expect($team_2->fresh()->score)->toBe(0);
    expect($team_3->fresh()->score)->toBe(0);
});
