<?php

use Livewire\Livewire;
use App\Livewire\SecretsPage;
use Thunk\Verbs\Facades\Verbs;
use App\Challenges\Classes\TeamFiller;
use App\Modifiers\Classes\TeamSecretCodes;
use App\Livewire\ModifierConfigurationPage;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
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

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'team',
        modifiers: $modifiers,
        team_names: ['team1', 'team2', 'team3', 'team4'],
    );

    $this->createGame();

    $this->john_user = $this->game->fresh()->players->first()->user;
});

it('allows the admin to add secret codes', function () {
    Livewire::actingAs($this->john_user)->test(ModifierConfigurationPage::class, [
            'game' => $this->game,
            'modifier' => $this->game->modifiers->first(),
        ])->assertSee('Secret Codes')
        ->set('secretCodes', 'code1 ,  code2, code3')
        ->call('saveSecretCodes')
        ->assertHasNoErrors();

    $this->game->fresh()->start();

    $mod = $this->game->fresh()->modifiers->first();

    expect($mod->modifier_data['unused_codes'])->toBe(['code1', 'code2', 'code3']);
    expect(array_key_exists('unused_codes', $mod->modifier_data))->toBeTrue();
    expect(array_key_exists('used_codes', $mod->modifier_data))->toBeTrue();
    expect(array_key_exists('banned_player_ids', $mod->modifier_data))->toBeTrue();
});

it('redeems secret codes', function () {
    $this->game->fresh()->start();
    $mod = $this->game->fresh()->modifiers->first();
    $team = $this->game->teams->first();
    $player_1 = $this->createPlayer()->joinTeam($team);

    Livewire::actingAs($player_1->user)->test(SecretsPage::class, [
        'game' => $this->game,
        'modifier' => $mod,
    ])->set('code_input', '1234567890')->call('submit_code')
    ->assertHasNoErrors();

    expect($team->fresh()->hidden_score)->toBe(1);
    expect($mod->fresh()->modifier_data['unused_codes'])->toBe(['0987654321']);
    expect($mod->fresh()->modifier_data['used_codes'])->toBe(['1234567890']);
    expect($mod->fresh()->modifier_data['banned_player_ids'])->toBe([]);
});