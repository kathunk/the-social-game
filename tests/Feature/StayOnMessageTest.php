<?php

use App\Challenges\Classes\StayOnMessage;
use App\Events\PlayerSubmittedStayOnMessage;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// @todo update this test to use Livewire

it('runs the Stay on Message challenge', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [StayOnMessage::key()],
            'duration' => 10,
        ],
    ];

    $this->mockGameTemplate(challenges: $challenges, type: 'team');
    $this->createGame()->start();

    $challenge = $this->game->challenges->first();
    $team_1 = $this->game->teams->first();
    $team_2 = $this->game->teams->skip(1)->first();
    $team_3 = $this->game->teams->skip(2)->first();
    $player_1 = $this->createPlayer()->joinTeam($team_1);
    $player_2 = $this->createPlayer($team_1)->joinTeam($team_1);
    $player_3 = $this->createPlayer($team_2)->joinTeam($team_2);
    $player_4 = $this->createPlayer($team_2)->joinTeam($team_2);
    $player_5 = $this->createPlayer($team_2)->joinTeam($team_2);
    $player_6 = $this->createPlayer($team_2)->joinTeam($team_2);
    $player_7 = $this->createPlayer($team_2)->joinTeam($team_2);

    PlayerSubmittedStayOnMessage::fire(
        player_id: $player_1->id,
        game_id: $this->game->id,
        team_id: $player_1->fresh()->team_id,
        challenge_id: $challenge->id,
        message: 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
    );

    expect($challenge->state()->challenge_data[$player_1->team_id][$player_1->id])
        ->toBe('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');

    expect($challenge->fresh()->challenge_data[$player_1->team_id][$player_1->id])
        ->toBe('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');

    PlayerSubmittedStayOnMessage::fire(
        player_id: $player_2->id,
        game_id: $this->game->id,
        team_id: $player_2->fresh()->team_id,
        challenge_id: $challenge->id,
        message: 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa',
    );

    PlayerSubmittedStayOnMessage::fire(
        player_id: $player_3->id,
        game_id: $this->game->id,
        team_id: $player_3->fresh()->team_id,
        challenge_id: $challenge->id,
        message: 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
    );

    PlayerSubmittedStayOnMessage::fire(
        player_id: $player_4->id,
        game_id: $this->game->id,
        team_id: $player_4->fresh()->team_id,
        challenge_id: $challenge->id,
        message: 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb',
    );

    PlayerSubmittedStayOnMessage::fire(
        player_id: $player_5->id,
        game_id: $this->game->id,
        team_id: $player_5->fresh()->team_id,
        challenge_id: $challenge->id,
        message: 'cccccccccccccccccccccccccccccccccccccccccccccccccc',
    );

    $challenge->fresh()->end();

    // both members of team 1 had the same answer. perfect score.
    expect($team_1->fresh()->score)->toBe(50);

    // team 2 had two of the same answer, one different answer, and two non-answers
    expect($team_2->fresh()->score)->toBe(-10);

    // team 3 had 0/0 and gets 0 points
    expect($team_3->fresh()->score)->toBe(0);
});
