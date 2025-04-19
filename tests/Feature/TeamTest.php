<?php

use Livewire\Livewire;
use App\Livewire\Dashboard;
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
    $team = $this->game->teams->skip(0)->first();
    $team2 = $this->game->teams->skip(1)->first();
    $team3 = $this->game->teams->skip(2)->first();

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

    expect($this->player->state()->previous_team_ids)->toContain($team->id);

    $this->player->joinTeam($team3);
    $this->player->refresh();
    expect($this->player->fresh()->team_id)->toBe($team3->id);
    expect($team3->fresh()->players->count())->toBe(1);
    expect($this->player->fresh()->last_switched_team_at)->not->toBeNull();

    expect($this->player->state()->previous_team_ids)
        ->toContain($team->id)
        ->toContain($team2->id);
});

it('players may only be on a particular team once', function () {
    $team = $this->game->teams->skip(0)->first();
    $team2 = $this->game->teams->skip(1)->first();

    $this->player->joinTeam($team);
    $this->player->refresh();

    $this->player->joinTeam($team2);
    $this->player->refresh();

    expect(fn() => $this->player->joinTeam($team))->toThrow('Player has already been on this team');
});

it('players will not be able to select previous teams', function () {
    $team = $this->game->teams->skip(0)->first();
    $team2 = $this->game->teams->skip(1)->first();

    $dashboard = fn() => Livewire::actingAs($this->player->refresh()->user)->test(Dashboard::class);

    $dashboard()
        ->set('selected_team_id', $team->id)
        ->call('joinTeam');

    $dashboard()
        ->set('selected_team_id', $team2->id)
        ->call('joinTeam');

    $dashboard()
        ->assertSee("disabled")
        ->assertSee($team->name . " (previous team)");
});
