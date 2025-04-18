<?php

use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();
    $this->createPlayer();
});

it('creates 10 teams at the start of the game', function () {
    expect($this->game->fresh()->teams->count())->toBe(10);
});

it('new players can join a team', function () {
    $team = $this->game->teams->first();
    $this->player->joinTeam($team);

    expect($this->player->fresh()->team_id)->toBe($team->id);
    expect($team->fresh()->players->count())->toBe(1);
});

it('players can switch teams', function () {
    $team = $this->game->teams->first();
    $team2 = $this->game->teams->last();
    $this->player->joinTeam($team);
    $this->player->refresh();

    expect($this->player->fresh()->team_id)->toBe($team->id);
    expect($team->fresh()->players->count())->toBe(1);
    expect($this->player->fresh()->last_switched_team_at)->toBeNull();

    $this->player->joinTeam($team2);
    $this->player->refresh();
    expect($this->player->fresh()->team_id)->toBe($team2->id);
    expect($team2->fresh()->players->count())->toBe(1);
    expect($this->player->fresh()->last_switched_team_at)->not->toBeNull();
});
