<?php

use App\Challenges\Laracon2025\TeamBounty;
use App\Challenges\TeamFiller;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Support\Facades\Date;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();

    // @todo we need to run a simple challenge first because we need for all the teams to have at least 3 players BEFORE Bounty starts
    $challenges = [
        [
            'challenge_keys' => [TeamFiller::key()],
            'duration' => 30,
        ],
        [
            'challenge_keys' => [TeamBounty::key()],
            'duration' => 30,
        ],
    ];

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'team',
        team_names: ['Team 1', 'Team 2', 'Team 3', 'Team 4', 'Team 5', 'Team 6', 'Team 7']
    );

    $this->createGame()->start();

    $this->team_1 = $this->game->teams->first();
    $this->team_2 = $this->game->teams->skip(1)->first();
    $this->team_3 = $this->game->teams->skip(2)->first();
    $this->team_4 = $this->game->teams->skip(3)->first();
    $this->team_5 = $this->game->teams->skip(4)->first();

    // Create 3 players per team
    $this->player_1 = $this->createPlayer()->joinTeam($this->team_1);
    $this->player_2 = $this->createPlayer()->joinTeam($this->team_1);
    $this->player_3 = $this->createPlayer()->joinTeam($this->team_1);

    $this->player_4 = $this->createPlayer()->joinTeam($this->team_2);
    $this->player_5 = $this->createPlayer()->joinTeam($this->team_2);
    $this->player_6 = $this->createPlayer()->joinTeam($this->team_2);

    $this->player_7 = $this->createPlayer()->joinTeam($this->team_3);
    $this->player_8 = $this->createPlayer()->joinTeam($this->team_3);
    $this->player_9 = $this->createPlayer()->joinTeam($this->team_3);

    $this->player_10 = $this->createPlayer()->joinTeam($this->team_4);
    $this->player_11 = $this->createPlayer()->joinTeam($this->team_4);
    $this->player_12 = $this->createPlayer()->joinTeam($this->team_4);

    $end = $this->game->fresh()->currentChallenge->ends_at;
    Date::setTestNow($end->addSeconds(1));
    $this->artisan('app:progress-games');

    $this->challenge = $this->game->fresh()->currentChallenge;
});

it('assigns bounties to teams on challenge start', function () {
    $bounties = $this->challenge->fresh()->challenge_data['team_bounties'];

    expect(count($bounties))->toBe(7);
    expect(collect($bounties)->every(fn ($bounty) => count($bounty) === 3))->toBeTrue();

    foreach ($bounties as $assigned_team_id => $bounty_player_ids) {
        $bounty_players = collect($bounty_player_ids)->map(fn ($id) => Player::find($id));
        $bounty_players_team_ids = $bounty_players->map(fn ($p) => $p->team_id);

        // bounty players should not be from their assigned team
        expect($bounty_players_team_ids->doesntContain($assigned_team_id))->toBeTrue();

        // each bounty player should be from a unique team
        expect($bounty_players_team_ids->count() === $bounty_players_team_ids->unique()->count())->toBeTrue();
    }
});

it('only awards points when a team recruits their bounty', function () {
    $bounties = $this->challenge->state()->fresh()->challenge_data['team_bounties'];

    foreach ($bounties as $assigned_team_id => $bounty_player_ids) {
        $team = Team::find($assigned_team_id);

        // recruit a bounty player assigned to the team
        $bounty_player = Player::query()
            ->whereNotIn('id', $this->challenge->state()->fresh()->challenge_data['swapper_ids'])
            ->where('team_id', '!=', $assigned_team_id)
            ->whereIn('id', $bounty_player_ids)
            ->first();

        if (! $bounty_player) {
            return;
        }

        swapTeam($bounty_player, $team->id, TeamBounty::key());
        expect($bounty_player->refresh()->team_id)->toBe($assigned_team_id);

        // assigned team gets 25 points for recruiting their bounty
        expect($team->fresh()->score)->toBe(25);

        // recruit a player not assigned to the team
        $non_bounty_player = Player::query()
            ->whereNotIn('id', $this->challenge->state()->fresh()->challenge_data['swapper_ids'])
            ->where('team_id', '!=', $assigned_team_id)
            ->whereNotIn('id', $bounty_player_ids)
            ->first();

        swapTeam($non_bounty_player, $team->id, TeamBounty::key());
        expect($non_bounty_player->refresh()->team_id)->toBe($assigned_team_id);

        // assigned team does not get points for recruiting a non-bounty player
        expect($team->fresh()->score)->toBe(25);
    }
});

it('players may only switch teams once during this challenge', function () {
    $player = $this->player_1;

    // First team switch should work
    swapTeam($player->fresh(), $this->team_2->id, TeamBounty::key());

    // Second team switch has no effect
    swapTeam($player->fresh(), $this->team_3->id, TeamBounty::key())
        ->assertHasErrors();

    expect($player->fresh()->team_id)->toBe($this->team_2->id);

    expect($this->challenge->fresh()->challenge_data['swapper_ids'])->toContain($player->id);

    expect($this->challenge->handler()->playerCanSwapTeams($player->fresh()))->toBeFalse();
});

describe('validate swapTeam', function () {
    it('team_id is required', function () {
        $player = $this->player_1;

        swapTeam($player->fresh(), '', TeamBounty::key())
            ->assertHasErrors(['round_properties.'.TeamBounty::key().'.team_id' => 'required']);
    });

    it('team_id must be a valid team', function () {
        $player = $this->player_1;

        swapTeam($player->fresh(), 999, TeamBounty::key())
            ->assertHasErrors(['round_properties.'.TeamBounty::key().'.team_id' => 'exists']);
    });
});

it('prevents Bounty from going first', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [TeamBounty::key()],
            'duration' => 10,
        ],
    ];

    expect(function () use ($challenges) {
        $this->mockGameTemplate(
            challenges: $challenges,
            type: 'individual',
        );
    })->toThrow(Exception::class, 'The following challenges are invalid for this template: Bounty cannot go first.');
});

it('can handle a verbs replay', function () {
    $bounties = $this->challenge->fresh()->challenge_data['team_bounties'];

    $this->artisan('db:reset-data');
    $this->artisan('verbs:replay');

    $new_bounties = $this->challenge->fresh()->challenge_data['team_bounties'];

    expect($new_bounties)->toBe($bounties);
});
