<?php

use App\Challenges\Classes\IndividualFiller;
use App\Livewire\GameDashboard;
use App\Modifiers\Classes\IndividualRecruiter;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('allows for resignations in individual games', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualFiller::key()],
            'duration' => 10,
        ],
    ];

    $modifiers = [IndividualRecruiter::key()];

    $this->mockGameTemplate(challenges: $challenges, type: 'individual', modifiers: $modifiers);

    $this->createGame()->start();

    $team = $this->game->teams->first();

    $player_1 = $this->createPlayer();
    $player_2 = $this->createPlayer();
    $player_3 = $this->createPlayer();

    $this->actingAs($player_1->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('Pyramid Scheme')
        ->set('round_properties.'.IndividualRecruiter::key().'.beneficiary_id', $player_2->id)
        ->call('callClassAction', 'give_referral_bonus', 'modifier', IndividualRecruiter::key())
        ->assertHasNoErrors();

    expect($player_2->fresh()->score)->toBe(0);
    expect($player_2->fresh()->hidden_score)->toBe(1);

    $referree_ids = $this->game->modifiers->first()->modifier_data['referree_ids'];

    expect($referree_ids)->toContain($player_1->id);
    expect($referree_ids)->not->toContain($player_2->id);
});
