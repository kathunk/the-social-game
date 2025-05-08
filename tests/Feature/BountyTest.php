<?php

use App\Models\Player;
use Thunk\Verbs\Facades\Verbs;
use Illuminate\Support\Facades\Date;
use App\Challenges\Classes\TeamBounty;
use App\Challenges\Classes\PyramidScheme;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::fake();
    Verbs::commitImmediately();

    // @todo we need to run a simple challenge first because we need for all the teams to have at least 3 players BEFORE Bounty starts
    $challenges = [
        [
            'challenge_keys' => [PyramidScheme::key()],
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
        team_names: ['Team 1', 'Team 2', 'Team 3', 'Team 4']
    );

    $this->createGame()->start();

    $this->team_1 = $this->game->teams->first();
    $this->team_2 = $this->game->teams->skip(1)->first();
    $this->team_3 = $this->game->teams->skip(2)->first();
    $this->team_4 = $this->game->teams->skip(3)->first();

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
    $this->challenge = $this->game->fresh()->currentChallenge;

    $bounties = $this->challenge->state()->fresh()->challenge_data['team_bounties'];

    expect(count($bounties))->toBe(4);
    expect(collect($bounties)->every(fn($bounty) => count($bounty) === 3))->toBeTrue();

    // Each team's bounty list should have players from other teams
    foreach ($bounties as $team_id => $player_ids) {
        expect(count($player_ids))->toBe(3);

        $players = collect($player_ids)->map(fn($id) => Player::find($id));
        $players->each(function($player) use ($team_id) {
            expect($player->team_id)->not->toBe($team_id);
        });
    }
});

it('only awards points when a team recruits their bounty', function () {
    $bounties = $this->challenge->challenge_data['team_bounties'];

    // recruit the first bounty player for team_1
    $bounty_player_id = $bounties[$this->team_1->id][0];
    $bounty_player = Player::find($bounty_player_id);

    $bounty_player->joinTeam($this->team_1);

    // recruit a player who is not one of our team's bounty players
    $non_bounty_player = Player::query()
        ->whereNotIn('id', $bounties[$this->team_1->id])
        ->where('team_id', '!=', $this->team_1->id)
        ->first();

    $non_bounty_player->joinTeam($this->team_1);

    // Team gets 15 points for recruiting their bounty
    expect($this->team_1->fresh()->score)->toBe(15);
});

it('prevents players from switching teams multiple times', function () {
    $player = $this->player_1;

    // First team switch should work
    $player->fresh()->joinTeam($this->team_2);

    expect(fn() => $player->fresh()->joinTeam($this->team_3))->toThrow(Exception::class);

    expect($this->challenge->fresh()->challenge_data['swapper_ids'])->toContain($player->id);

    expect($this->challenge->handler()->playerCanSwapTeams($player->fresh()))->toBeFalse();
});
