<?php

use App\Challenges\Classes\IndividualFiller;
use App\Livewire\GameDashboard;
use App\Modifiers\Classes\IndividualResignation;
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

    $modifiers = [IndividualResignation::key()];

    $this->mockGameTemplate(challenges: $challenges, type: 'individual', modifiers: $modifiers);

    $this->createGame()->start();

    $team = $this->game->teams->first();

    $player_1 = $this->createPlayer();
    $player_2 = $this->createPlayer();
    $player_3 = $this->createPlayer();

    incrementScore(points: -5, player: $player_1);
    incrementScore(points: 3, player: $player_1, is_hidden: true);

    $this->actingAs($player_1->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('Had enough?')
        ->set('round_properties.'.IndividualResignation::key().'.points_beneficiary_id', $player_2->id)
        ->set('round_properties.'.IndividualResignation::key().'.hidden_points_beneficiary_id', $player_3->id)
        ->call('callClassAction', 'resign', 'modifier', IndividualResignation::key())
        ->assertHasNoErrors();

    expect($player_1->fresh()->score)->toBe(0);
    expect($player_1->fresh()->hidden_score)->toBe(0);
    expect($player_1->fresh()->status)->toBe('resigned');

    expect($player_2->fresh()->score)->toBe(-5);
    expect($player_2->fresh()->hidden_score)->toBe(-5);

    expect($player_3->fresh()->score)->toBe(0);
    expect($player_3->fresh()->hidden_score)->toBe(3);
});
