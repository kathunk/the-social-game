<?php

use App\Challenges\TeamFiller;
use App\Challenges\Laracon2025\TeamHotPotato;
use App\Livewire\GameDashboard;
use App\Models\Player;
use Illuminate\Support\Facades\Date;
use Livewire\Features\SupportTesting\Testable as LivewireTest;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [TeamFiller::key()],
            'duration' => 1,
        ],
        [
            'challenge_keys' => [TeamHotPotato::key()],
            'duration' => 1,
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
    $this->player_2 = $this->createPlayer()->joinTeam($this->team_1);
    $this->player_3 = $this->createPlayer()->joinTeam($this->team_1);
    $this->player_4 = $this->createPlayer()->joinTeam($this->team_1);

    $this->player_5 = $this->createPlayer()->joinTeam($this->team_2);
    $this->player_6 = $this->createPlayer()->joinTeam($this->team_2);
    $this->player_7 = $this->createPlayer()->joinTeam($this->team_2);
    $this->player_8 = $this->createPlayer()->joinTeam($this->team_2);

    $this->player_9 = $this->createPlayer()->joinTeam($this->team_3);
    $this->player_10 = $this->createPlayer()->joinTeam($this->team_3);
    $this->player_11 = $this->createPlayer()->joinTeam($this->team_3);
    $this->player_12 = $this->createPlayer()->joinTeam($this->team_3);

    $end = $this->game->fresh()->currentChallenge->ends_at;
    Date::setTestNow($end->addSeconds(1));
    $this->artisan('app:progress-games');
});

function passPotato($player, $recipient): LivewireTest
{
    return Livewire::actingAs($player->user)
        ->test(GameDashboard::class, ['game' => $player->game->fresh()])
        ->set('round_properties.'.TeamHotPotato::key().'.recipient_player_id', $recipient->id)
        ->call('callClassAction', 'passThePotato', 'challenge', TeamHotPotato::key())->assertHasNoErrors();
}

it('sets the expected challenge data', function () {
    $team_1_data = $this->game->fresh()->currentChallenge->challenge_data[$this->team_1->id];

    $holder_id = $team_1_data['potato_holder_id'];
    $remaining_player_ids = $team_1_data['remaining_player_ids'];
    $all_holder_ids = $team_1_data['all_holder_ids'];

    expect(count($remaining_player_ids))->toBe(3);
    expect($remaining_player_ids)->not->toContain($holder_id);

    expect($all_holder_ids)->toContain($holder_id);
    expect(count($all_holder_ids))->toBe(1);
});

it('passes the potato to the intended recipient', function () {
    $team_1_data = $this->game->fresh()->currentChallenge->challenge_data[$this->team_1->id];

    $holder_id = $team_1_data['potato_holder_id'];
    $remaining_player_ids = $team_1_data['remaining_player_ids'];

    $holder = Player::find($holder_id);
    $valid_recipient = Player::find(collect($remaining_player_ids)->random());

    passPotato($holder, $valid_recipient);

    $team_1_data = $this->game->fresh()->currentChallenge->challenge_data[$this->team_1->id];

    expect($team_1_data['potato_holder_id'])->toBe($valid_recipient->id);
    expect($team_1_data['remaining_player_ids'])->not->toContain($holder_id);
    expect($team_1_data['remaining_player_ids'])->not->toContain($valid_recipient->id);
    expect($team_1_data['all_holder_ids'])->toContain($valid_recipient->id);
    expect(count($team_1_data['all_holder_ids']))->toBe(2);
});

it('does not penalize teams with zero players', function () {
    $new_player = $this->createPlayer()->joinTeam($this->team_4);

    Livewire::actingAs($new_player->user)
        ->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('Challenge forfeited. No players were on the team when the challenge started.');

    $end = $this->game->fresh()->currentChallenge->ends_at;
    Date::setTestNow($end->addSeconds(1));
    $this->artisan('app:progress-games');

    expect($this->team_4->fresh()->score)->toBe(0);
});

it('ends the challenge when the potato is passed to a repeat player', function () {
    $team_1_data = $this->game->fresh()->currentChallenge->challenge_data[$this->team_1->id];
    $holder_id = $team_1_data['potato_holder_id'];
    $remaining_player_ids = $team_1_data['remaining_player_ids'];
    $holder = Player::find($holder_id);
    $valid_recipient = Player::find(collect($remaining_player_ids)->random());

    passPotato($holder, $valid_recipient);

    passPotato($valid_recipient, $holder);

    $team_status = $this->game->fresh()->currentChallenge->challenge_data[$this->team_1->id]['status'];

    expect($team_status)->toBe('failed');
    expect($this->team_1->fresh()->score)->toBe(-50);

    Livewire::actingAs($this->player_1->user)
        ->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('Sorry folks, try again next time.');
});

it('gives expected scores', function () {
    $team_2_data = $this->game->fresh()->currentChallenge->challenge_data[$this->team_2->id];
    $holder_id = $team_2_data['potato_holder_id'];
    $remaining_player_ids = $team_2_data['remaining_player_ids'];
    $holder = Player::find($holder_id);
    $valid_recipient = Player::find(collect($remaining_player_ids)->random());

    passPotato($holder, $valid_recipient);

    foreach (range(1, 3) as $i) {
        $team_3_data = $this->game->fresh()->currentChallenge->challenge_data[$this->team_3->id];
        $holder_id = $team_3_data['potato_holder_id'];
        $remaining_player_ids = $team_3_data['remaining_player_ids'];
        $holder = Player::find($holder_id);
        $valid_recipient = Player::find(collect($remaining_player_ids)->random());
        passPotato($holder, $valid_recipient);
    }

    $fresh_team_3_data = $this->game->fresh()->currentChallenge->challenge_data[$this->team_3->id];
    expect($fresh_team_3_data['status'])->toBe('succeeded');
    expect($fresh_team_3_data['potato_holder_id'])->toBeNull();

    Livewire::actingAs($this->player_10->user)
        ->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('You did it!');

    $end = $this->game->fresh()->currentChallenge->ends_at;
    Date::setTestNow($end->addSeconds(1));
    $this->artisan('app:progress-games');

    // team 1 does nothing, resulting in -25 points
    expect($this->team_1->fresh()->score)->toBe(-25);

    // team 2 does 1 pass, resulting in 0 points
    expect($this->team_2->fresh()->score)->toBe(0);

    // team 3 does all 3 passes, resulting in 50 points
    expect($this->team_3->fresh()->score)->toBe(50);
});

it('prevents Hot Potato from going first', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [TeamHotPotato::key()],
            'duration' => 10,
        ],
    ];

    expect(function () use ($challenges) {
        $this->mockGameTemplate(
            challenges: $challenges,
            type: 'individual',
        );
    })->toThrow(Exception::class, 'The following challenges are invalid for this template: Hot Potato cannot go first.');
});

it('can handle a verbs replay', function () {
    Verbs::commit();

    $challenge_data = $this->game->fresh()->currentChallenge->challenge_data;

    $this->artisan('db:reset-data');
    $this->artisan('verbs:replay');

    $new_challenge_data = $this->game->fresh()->currentChallenge->challenge_data;

    expect($new_challenge_data)->toBe($challenge_data);
});
