<?php

use App\Challenges\Classes\TeamFiller;
use App\Livewire\SecretsPage;
use App\Modifiers\Classes\TeamSecretCodes;
use Illuminate\Support\Facades\Date;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('handles secret codes in team games', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [TeamFiller::key()],
            'duration' => 10,
        ],
    ];

    $modifiers = [
        TeamSecretCodes::key(),
    ];

    $this->mockGameTemplate(challenges: $challenges, type: 'team', modifiers: $modifiers, team_names: ['team1', 'team2', 'team3', 'team4']);

    $this->createGame()->start();

    $mod = $this->game->modifiers->first();

    $team = $this->game->teams->first();
    $team_2 = $this->game->teams->skip(1)->first();
    $team_3 = $this->game->teams->skip(2)->first();
    $team_4 = $this->game->teams->skip(3)->first();

    $player_1 = $this->createPlayer();

    $this->actingAs($player_1->user);

    Livewire::test(SecretsPage::class, [
        'game' => $this->game,
        'modifier' => $this->game->modifiers->first(),
    ])
        ->assertStatus(200)
        ->assertSee('Join a team before you can discover your secret ally');

    $player_1->joinTeam($team);
    $player_2 = $this->createPlayer()->joinTeam($team_2);

    $this->actingAs($player_1->user);

    // players 1 and 2 get paired simply by visiting this page
    Livewire::test(SecretsPage::class, [
        'game' => $this->game->fresh(),
        'modifier' => $this->game->modifiers->first()->fresh(),
    ]);

    $pair_data = $this->game->modifiers->first()->fresh()->modifier_data['pairs'];

    expect(collect($pair_data)->count())->toBe(1);
    expect($pair_data)->toBe([
        [
            'player_1_id' => $player_1->id,
            'player_2_id' => $player_2->id,
            'player_1_original_team_id' => $team->id,
            'player_2_original_team_id' => $team_2->id,
            'has_connected' => false,
        ],
    ]);

    $player_1->fresh()->joinTeam($team_3);
    $player_2->fresh()->joinTeam($team_3);

    expect($team_3->fresh()->score)->toBe(0);
    expect($team_3->fresh()->hidden_score)->toBe(5);

    $pair_data = $this->game->modifiers->first()->fresh()->modifier_data['pairs'];

    expect($pair_data)->toBe([
        [
            'player_1_id' => $player_1->id,
            'player_2_id' => $player_2->id,
            'player_1_original_team_id' => $team->id,
            'player_2_original_team_id' => $team_2->id,
            'has_connected' => true,
        ],
    ]);

    $player_3 = $this->createPlayer()->joinTeam($team_2);
    $player_4 = $this->createPlayer()->joinTeam($team_2);

    $this->actingAs($player_3->user);

    // since the only un-allied player is on their team, there is no valid ally
    Livewire::test(SecretsPage::class, [
        'game' => $this->game->fresh(),
        'modifier' => $this->game->modifiers->first()->fresh(),
    ])
        ->assertSee('Unfortunately there are no eligible partners for you right now. Try again later.');

    $end = $this->game->fresh()->currentChallenge->ends_at;
    Date::setTestNow($end->addSeconds(1));
    $this->artisan('app:progress-games');

    $player_1->fresh()->joinTeam($team_4);
    $player_2->fresh()->joinTeam($team_4);

    // since they have already realized this reward, it does not give them an additional reward
    expect($team_4->fresh()->score)->toBe(0);
    expect($team_4->fresh()->hidden_score)->toBe(0);
});
