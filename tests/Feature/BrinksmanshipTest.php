<?php

use Thunk\Verbs\Facades\Verbs;
use Illuminate\Support\Facades\Date;
use App\Challenges\Classes\Brinksmanship;
use App\Events\PlayerSubmittedNuclearStrike;
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
            'challenge_keys' => [Brinksmanship::key()],
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

    expect($this->team_1->fresh()->score)->toBe(20);
    expect($this->team_2->fresh()->score)->toBe(20);
    expect($this->team_3->fresh()->score)->toBe(20);
    expect($this->team_4->fresh()->score)->toBe(20);

    // Find ally team for team 1
    $this->team_1_ally_id = $this->challenge_data['teams'][$this->team_1->id]['ally_team_id'];
    $this->team_1_ally = $this->game->teams->firstWhere('id', $this->team_1_ally_id);

    // Get nuclear codes
    $this->team_1_code = $this->challenge_data['teams'][$this->team_1->id]['code'];
    $this->team_1_ally_code = $this->challenge_data['teams'][$this->team_1_ally_id]['code'];

    $this->player_1_ally = $this->team_1_ally->players->first();
});

function launchNuclearStrike($player, $strike_type, $target_code)
{
    PlayerSubmittedNuclearStrike::fire(
        player_id: $player->id,
        game_id: $player->game_id,
        challenge_id: $player->game->fresh()->currentChallenge->id,
        team_id: $player->team_id,
        strike_type: $strike_type,
        target_code: $target_code,
    );
}

it('creates unique codes for each team and assigns ally pairs', function () {
    $challenge_data = $this->game->fresh()->currentChallenge->challenge_data;
    $teams_data = $challenge_data['teams'];

    // Check all teams have a code and ally
    foreach ($this->game->teams as $team) {
        expect($teams_data[$team->id])->toHaveKey('code');
        expect($teams_data[$team->id])->toHaveKey('ally_team_id');

        // Ally relationship should be reciprocal
        $ally_id = $teams_data[$team->id]['ally_team_id'];
        expect($teams_data[$ally_id]['ally_team_id'])->toBe($team->id);

        // Codes should be 6 characters long
        expect(strlen($teams_data[$team->id]['code']))->toBe(6);
    }

    // All codes should be unique
    $codes = collect($teams_data)->pluck('code');
    expect($codes->unique()->count())->toBe($codes->count());
});

it('requires valid ally code to launch nuclear strikes', function () {
    // This should fail validation with an invalid code
    expect(function () {
        launchNuclearStrike($this->player_1, 'carpet_bomb', 'INVALID');
    })->toThrow(\Exception::class);

    launchNuclearStrike($this->player_1_ally, 'carpet_bomb', $this->team_1_code);

    // Verify the strike was recorded
    $challenge = $this->game->fresh()->currentChallenge;
    $team_1_data = $challenge->challenge_data['teams'][$this->team_1->id];
    $team_1_ally_data = $challenge->challenge_data['teams'][$this->team_1_ally->id];

    expect($team_1_data['has_launched'])->toBeFalse();
    expect($team_1_data['strike_type'])->toBeNull();

    expect($team_1_ally_data['has_launched'])->toBeTrue();
    expect($team_1_ally_data['strike_type'])->toBe('carpet_bomb');
});

it('gives -10 points to all other teams when a team launches a carpet bomb', function () {
    // Team 1 carpet bombs all other teams
    launchNuclearStrike($this->player_1, 'carpet_bomb', $this->team_1_ally_code);

    // End challenge
    $end = $this->game->fresh()->currentChallenge->ends_at;
    Date::setTestNow($end->addSeconds(1));
    $this->artisan('app:progress-games');

    // Team 1 score remains unchanged
    expect($this->team_1->fresh()->score)->toBe(20);

    // All other teams lose 10 points
    expect($this->team_2->fresh()->score)->toBe(10);
    expect($this->team_3->fresh()->score)->toBe(10);
    expect($this->team_4->fresh()->score)->toBe(10);
});

it('gives -40 points to ally team when a team launches a nuke ally strike', function () {
    // Team 1 and Team 1 Ally nuke one another
    launchNuclearStrike($this->player_1, 'nuke_ally', $this->team_1_ally_code);
    launchNuclearStrike($this->player_1_ally, 'nuke_ally', $this->team_1_code);

    // End challenge
    $end = $this->game->fresh()->currentChallenge->ends_at;
    Date::setTestNow($end->addSeconds(1));
    $this->artisan('app:progress-games');

    expect($this->team_1->fresh()->score)->toBe(-20);
    expect($this->team_1_ally->fresh()->score)->toBe(-20);

    // Find a team that is not Team 1 or its ally
    $other_team = $this->game->teams->first(function ($team) {
        return $team->id !== $this->team_1->id && $team->id !== $this->team_1_ally_id;
    });

    // Other teams' scores remain unchanged
    expect($other_team->fresh()->score)->toBe(20);
});

it('prevents a team from launching multiple strikes', function () {
    // First strike should succeed
    launchNuclearStrike($this->player_1, 'carpet_bomb', $this->team_1_ally_code);

    // Second strike should fail
    expect(function () {
        launchNuclearStrike($this->player_1, 'nuke_ally', $this->team_1_ally_code);
    })->toThrow(\Exception::class);
});
