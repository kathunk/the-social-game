<?php

use Thunk\Verbs\Facades\Verbs;
use Illuminate\Support\Facades\Date;
use App\Events\PlayerSubmittedPlayDirty;
use App\Challenges\Classes\PrisonersDilemma;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [PrisonersDilemma::key()],
            'duration' => 10,
        ],
    ];

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'team',
        team_names: ['Team 1', 'Team 2', 'Team 3', 'Team 4'],
    );

    $this->createGame()->start();

    $this->team_1= $this->game->teams->first();
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

    incrementScore($this->team_1, 20);
    incrementScore($this->team_2, 20);

    incrementScore($this->team_3, 10);
    incrementScore($this->team_4, 10);
});

function playDirty($player)
{
    PlayerSubmittedPlayDirty::fire(
        player_id: $player->id,
        game_id: $player->game_id,
        challenge_id: $player->game->fresh()->currentChallenge->id,
        team_id: $player->team_id,
    );;
}

it('both teams play dirty they will each get -20 points', function () {
    playDirty($this->player_1);
    playDirty($this->player_3);

    // end challenge
    $end = $this->game->fresh()->currentChallenge->ends_at;
    Date::setTestNow($end->addSeconds(1));
    $this->artisan('app:progress-games');

    expect($this->team_1->score)->toBe(0);
    expect($this->team_2->score)->toBe(0);
});

it('if you play dirty and they do not you will get 50 points', function () {
    playDirty($this->player_1);

    // end challenge
    $end = $this->game->fresh()->currentChallenge->ends_at;
    Date::setTestNow($end->addSeconds(1));
    $this->artisan('app:progress-games');

    expect($this->team_1->fresh()->score)->toBe(70);
    expect($this->team_2->fresh()->score)->toBe(20);
});

it('if neither plays dirty they will each get 20 points', function () {
    // end challenge
    $end = $this->game->fresh()->currentChallenge->ends_at;
    Date::setTestNow($end->addSeconds(1));
    $this->artisan('app:progress-games');

    expect($this->team_1->fresh()->score)->toBe(40);
    expect($this->team_2->fresh()->score)->toBe(40);
});
