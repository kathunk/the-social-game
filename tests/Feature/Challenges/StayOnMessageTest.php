<?php

use App\Challenges\Classes\Laracon2025\StayOnMessage;
use App\Livewire\GameDashboard;
use Livewire\Features\SupportTesting\Testable as LivewireTest;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function stayOnMessage($player, $message): LivewireTest
{
    return Livewire::actingAs($player->user)
        ->test(GameDashboard::class, ['game' => $player->game->fresh()])
        ->set('round_properties.'.StayOnMessage::key().'.string_input', $message)
        ->call('callClassAction', 'submitString', 'challenge', StayOnMessage::key());
}

beforeEach(function () {
    Verbs::fake();
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [StayOnMessage::key()],
            'duration' => 10,
        ],
    ];

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'team',
        team_names: ['Team 1', 'Team 2', 'Team 3', 'Team 4'],
    );

    $this->createGame()->start();
});

it('runs the Stay on Message challenge', function () {
    $challenge = $this->game->challenges->first();
    $team_1 = $this->game->teams->first();
    $team_2 = $this->game->teams->skip(1)->first();
    $team_3 = $this->game->teams->skip(2)->first();
    $player_1 = $this->createPlayer()->joinTeam($team_1);
    $player_2 = $this->createPlayer($team_1)->joinTeam($team_1);
    $player_3 = $this->createPlayer($team_2)->joinTeam($team_2);
    $player_4 = $this->createPlayer($team_2)->joinTeam($team_2);
    $player_5 = $this->createPlayer($team_2)->joinTeam($team_2);
    $player_6 = $this->createPlayer($team_2)->joinTeam($team_2);
    $player_7 = $this->createPlayer($team_2)->joinTeam($team_2);

    stayOnMessage($player_1, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa')
        ->assertHasNoErrors();

    expect($challenge->state()->challenge_data[$player_1->team_id][$player_1->id])
        ->toBe('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');

    expect($challenge->fresh()->challenge_data[$player_1->team_id][$player_1->id])
        ->toBe('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');

    stayOnMessage($player_2, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa')
        ->assertHasNoErrors();

    stayOnMessage($player_3, 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb')
        ->assertHasNoErrors();

    stayOnMessage($player_4, 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb')
        ->assertHasNoErrors();

    stayOnMessage($player_5, 'cccccccccccccccccccccccccccccccccccccccccccccccccc')
        ->assertHasNoErrors();

    $challenge->fresh()->end();

    // both members of team 1 had the same answer. perfect score.
    expect($team_1->fresh()->score)->toBe(50);

    // team 2 had two of the same answer, one different answer, and two non-answers
    expect($team_2->fresh()->score)->toBe(-10);

    // team 3 had 0/0 and gets 0 points
    expect($team_3->fresh()->score)->toBe(0);
});

describe('validate stayOnMessage', function () {
    beforeEach(function () {
        $this->player = $this->createPlayer()->joinTeam($this->game->teams->first());
    });

    it('is required', function () {
        stayOnMessage($this->player, '')
            ->assertHasErrors(['round_properties.'.StayOnMessage::key().'.string_input' => 'required']);
    });

    it('must be 50 min', function () {
        stayOnMessage($this->player, 'a')
            ->assertHasErrors(['round_properties.'.StayOnMessage::key().'.string_input' => 'min']);
    });

    it('must be 50 max', function () {
        stayOnMessage($this->player, str_repeat('a', 51))
            ->assertHasErrors(['round_properties.'.StayOnMessage::key().'.string_input' => 'max']);
    });
});
