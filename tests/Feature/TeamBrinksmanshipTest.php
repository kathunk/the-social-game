<?php

use App\Models\Challenge;
use Thunk\Verbs\Facades\Verbs;
use Illuminate\Support\Facades\Date;
use App\Events\PlayerSubmittedNuclearStrike;
use App\Challenges\Classes\TeamBrinksmanship;
use App\Challenges\Classes\TeamPrisonersDilemma;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::fake();
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [TeamPrisonersDilemma::key()],
            'duration' => 10,
        ],
        [
            'challenge_keys' => [TeamBrinksmanship::key()],
            'duration' => 10,
        ],
    ];

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'team',
        team_names: ['Team 1', 'Team 2', 'Team 3', 'Team 4'],
    );

    $this->createGame()->start();

    $this->team_1 = $this->game->teams->first();
    $this->team_2 = $this->game->teams->skip(1)->first();
    $this->team_3 = $this->game->teams->skip(2)->first();
    $this->team_4 = $this->game->teams->skip(3)->first();

    $this->player_1 = $this->createPlayer()->joinTeam($this->team_1);
    $this->player_2 = $this->createPlayer()->joinTeam($this->team_2);
    $this->player_3 = $this->createPlayer()->joinTeam($this->team_3);
    $this->player_4 = $this->createPlayer()->joinTeam($this->team_4);

    $end = $this->game->fresh()->currentChallenge->ends_at;
    Date::setTestNow($end->addSeconds(1));
    $this->artisan('app:progress-games');

    // Get challenge data to identify ally teams
    $this->challenge = $this->game->fresh()->currentChallenge;
    $this->challenge_data = $this->challenge->challenge_data;

    $this->data = fn($team) => $this->challenge_data[$team->id];
    $this->code = fn($team) => ($this->data)($team)['code'];
    $this->ally_team = fn($team) => $this->game->teams->firstWhere('id', ($this->data)($team)['ally_team_id']);

    expect($this->team_1->fresh()->score)->toBe(20);
    expect($this->team_2->fresh()->score)->toBe(20);
    expect($this->team_3->fresh()->score)->toBe(20);
    expect($this->team_4->fresh()->score)->toBe(20);
});

function launchNuclearStrike($player, $strike_type, $target_code)
{
    PlayerSubmittedNuclearStrike::fire(
        player_id: $player->id,
        game_id: $player->game_id,
        challenge_id: $player->game->currentChallenge->id,
        team_id: $player->team_id,
        strike_type: $strike_type,
        target_code: $target_code,
    );
}

it('creates unique codes for each team and assigns ally pairs', function () {
    // Check all teams have a code and ally
    foreach ($this->game->teams as $team) {
        $ally_team = ($this->ally_team)($team);

        $team_data = ($this->data)($team);
        $ally_data = ($this->data)($ally_team);

        expect($team_data)->toHaveKey('code');
        expect($team_data)->toHaveKey('ally_team_id');

        // Ally relationship should be reciprocal
        expect($ally_data['ally_team_id'])->toBe($team->id);
        expect($team_data['ally_team_id'])->toBe($ally_team->id);

        // Codes should be 6 characters long
        expect(strlen($team_data['code']))->toBe(6);
    }

    // All codes should be unique
    $codes = collect($this->game->teams->map(fn($t) => ($this->code)($t)));
    expect($codes->unique()->count())->toBe($codes->count());
});

it('requires valid ally code to launch nuclear strikes', function () {
    // This should fail validation with an invalid code
    expect(function () {
        launchNuclearStrike($this->player_1, 'carpet_bomb', 'INVALID');
    })->toThrow(\Exception::class);

    // Test valid launches for all teams
    foreach ($this->game->teams as $team) {
        $player = $team->players->first();
        $ally_team = ($this->ally_team)($team);
        $ally_code = ($this->code)($ally_team);

        launchNuclearStrike($player, 'carpet_bomb', $ally_code);

        // Refresh challenge data
        $this->challenge = $this->game->fresh()->currentChallenge;
        $this->challenge_data = $this->challenge->challenge_data;

        $team_data = ($this->data)($team);

        expect($team_data['has_launched'])->toBeTrue();
        expect($team_data['strike_type'])->toBe('carpet_bomb');
    }
});

it('gives -10 points to all other teams when a team launches a carpet bomb', function () {
    foreach ($this->game->teams as $team) {
        $player = $team->players->first();
        $ally_team = ($this->ally_team)($team);
        $ally_code = ($this->code)($ally_team);

        // Launch carpet bomb
        launchNuclearStrike($player, 'carpet_bomb', $ally_code);
    }

    $this->challenge->end();

    // all teams carpet bomb, so it's -30 points for each team
    foreach ($this->game->teams as $team) {
        expect($team->fresh()->score)->toBe(-10);
        expect($ally_team->fresh()->score)->toBe(-10);
    }
});

it('gives -40 points to ally team when a team launches a nuke ally strike', function () {
    foreach ($this->game->teams as $team) {
        $player = $team->players->first();
        $ally_team = ($this->ally_team)($team);
        $ally_code = ($this->code)($ally_team);

        // Teams nuke one another
        launchNuclearStrike($player, 'nuke_ally', $ally_code);
    }

    $this->challenge->end();

    // all teams ally strike, so it's -40 points for each team
    foreach ($this->game->teams as $team) {
        // Both teams should lose 40 points
        expect($team->fresh()->score)->toBe(-20);
        expect($ally_team->fresh()->score)->toBe(-20);
    }
});

it('prevents a team from launching multiple strikes', function () {
    foreach ($this->game->teams as $team) {
        $player = $team->players->first();
        $ally_team = ($this->ally_team)($team);
        $ally_code = ($this->code)($ally_team);

        // First strike should succeed
        launchNuclearStrike($player, 'carpet_bomb', $ally_code);

        // Second strike should fail
        expect(function () use ($player, $ally_code) {
            launchNuclearStrike($player, 'nuke_ally', $ally_code);
        })->toThrow(\Exception::class);
    }
});
