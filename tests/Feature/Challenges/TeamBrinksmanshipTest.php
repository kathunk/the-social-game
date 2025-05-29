<?php

use App\Challenges\Classes\TeamBrinksmanship;
use App\Livewire\GameDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable as LivewireTest;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Verbs::fake();
    Verbs::commitImmediately();

    $challenges = [
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

    $this->challenge = $this->game->fresh()->currentChallenge;
});

function data($team)
{
    return $team->game->fresh()->currentChallenge->challenge_data[$team->id];
}

function code($team)
{
    return data($team)['code'];
}

function ally_team($team)
{
    return $team->game->teams->firstWhere('id', data($team)['ally_team_id']);
}

function launchNuclearStrike($player, $strike_type, $code): LivewireTest
{
    $strike_type = match ($strike_type) {
        'carpet_bomb' => 'carpetBomb',
        'nuke_ally' => 'nukeAlly',
    };

    return Livewire::actingAs($player->user)
        ->test(GameDashboard::class, ['game' => $player->game->fresh()])
        ->set('round_properties.'.TeamBrinksmanship::key().'.target_code', $code)
        ->call('callClassAction', $strike_type, 'challenge', TeamBrinksmanship::key());
}

it('creates unique codes for each team and assigns ally pairs', function () {
    foreach ($this->game->teams as $team) {
        $ally_team = ally_team($team);

        $team_data = data($team);
        $ally_data = data($ally_team);

        expect($team_data)->toHaveKey('code');
        expect($team_data)->toHaveKey('ally_team_id');

        // Ally relationship should be reciprocal
        expect($ally_data['ally_team_id'])->toBe($team->id);
        expect($team_data['ally_team_id'])->toBe($ally_team->id);

        // Codes should be 6 characters long
        expect(strlen($team_data['code']))->toBe(6);
    }

    // All codes should be unique
    $codes = collect($this->game->teams->map(fn ($t) => code($t)));
    expect($codes->unique()->count())->toBe($codes->count());
});

it('requires valid ally code to launch nuclear strikes', function () {
    foreach ($this->game->teams as $team) {
        $player = $team->players->first();
        $ally_team = ally_team($team);
        $ally_code = code($ally_team);

        launchNuclearStrike($player, 'carpet_bomb', $ally_code)
            ->assertHasNoErrors();

        $team_data = data($team);

        expect($team_data['has_launched'])->toBeTrue();
        expect($team_data['strike_type'])->toBe('carpet_bomb');
    }
});

it('gives -5 points to all other teams when a team launches a carpet bomb', function () {
    foreach ($this->game->teams as $team) {
        $player = $team->players->first();
        $ally_team = ally_team($team);
        $ally_code = code($ally_team);

        launchNuclearStrike($player, 'carpet_bomb', $ally_code)
            ->assertHasNoErrors();
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
        $ally_team = ally_team($team);
        $ally_code = code($ally_team);

        launchNuclearStrike($player, 'nuke_ally', $ally_code)
            ->assertHasNoErrors();
    }

    $this->challenge->end();

    // all teams ally strike, so it's -40 points for each team
    foreach ($this->game->teams as $team) {
        expect($team->fresh()->score)->toBe(-40);
        expect($ally_team->fresh()->score)->toBe(-40);
    }
});

it('prevents a team from launching multiple strikes', function () {
    foreach ($this->game->teams as $team) {
        $player = $team->players->first();
        $ally_team = ally_team($team);
        $ally_code = code($ally_team);

        // First strike should succeed
        launchNuclearStrike($player, 'carpet_bomb', $ally_code);

        // Second strike should fail
        expect(function () use ($player, $ally_code) {
            launchNuclearStrike($player, 'nuke_ally', $ally_code);
        })->toThrow(\Exception::class);
    }
});

describe('validate nuclear code', function () {
    it('is required', function () {
        launchNuclearStrike($this->player_1, 'carpet_bomb', '')
            ->assertHasErrors([
                'round_properties.'.TeamBrinksmanship::key().'.target_code' => 'required',
            ]);
    });

    it('must be valid', function () {
        launchNuclearStrike($this->player_1, 'carpet_bomb', 'INVALID')
            ->assertHasErrors([
                'round_properties.'.TeamBrinksmanship::key().'.target_code' => 'nuclear_code',
            ]);
    });
});

it('cannot be used with an odd number of teams', function () {
    $challenges = [
        [
            'challenge_keys' => [TeamBrinksmanship::key()],
            'duration' => 10,
        ],
    ];

    expect(function () use ($challenges) {
        $this->mockGameTemplate(
            challenges: $challenges,
            type: 'team',
            team_names: ['Team 1', 'Team 2', 'Team 3'],
        );
    })->toThrow(Exception::class, 'Brinksmanship requires an even number of teams.');
});