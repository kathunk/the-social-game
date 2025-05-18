<?php

use App\Models\Player;
use Livewire\Livewire;
use App\Models\Challenge;
use Thunk\Verbs\Facades\Verbs;
use App\Livewire\GameDashboard;
use Illuminate\Support\Facades\Date;
use App\Challenges\Classes\PyramidScheme;
use App\Challenges\Classes\TeamHotPotato;
use Livewire\Features\SupportTesting\Testable as LivewireTest;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::fake();
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [PyramidScheme::key()],
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

    $end = $this->game->fresh()->currentChallenge->ends_at;
    Date::setTestNow($end->addSeconds(1));
    $this->artisan('app:progress-games');
});

function passPotato($player, $recipient): LivewireTest
{
    return Livewire::actingAs($player->user)
        ->test(GameDashboard::class, ['game' => $player->game->fresh()])
        ->set('challenge_properties.recipient_player_id', $recipient->id)
        ->call('callChallengeAction', 'passThePotato');
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
    $valid_recipient = Player::find($remaining_player_ids[0]);

    passPotato($holder, $valid_recipient);

    $team_1_data = $this->game->fresh()->currentChallenge->challenge_data[$this->team_1->id];

    expect($team_1_data['potato_holder_id'])->toBe($valid_recipient->id);
    expect($team_1_data['remaining_player_ids'])->not->toContain($holder_id);
    expect($team_1_data['remaining_player_ids'])->not->toContain($valid_recipient->id);
    expect($team_1_data['all_holder_ids'])->toContain($valid_recipient->id);
    expect(count($team_1_data['all_holder_ids']))->toBe(2);
});