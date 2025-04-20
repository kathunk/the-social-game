<?php

use App\Models\Team;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();
    $this->createGame();
    $this->createPlayer();
    $this->team = Team::first();
});

it('grants a ', function () {
    $this->player->joinTeam($this->team);
    $this->player->fresh()->resign(3);
    $this->player->refresh();
    $this->team->refresh();

    expect($this->player->status)->toBe('resigned');
    expect($this->player->team_id)->toBeNull();
    expect($this->team->score)->toBe(4);

    expect($this->team->players->count())->toBe(0);

    // @todo test that all the state information is correct too
});
