<?php

use App\Challenges\Classes\IndividualHighScoreQuiz;
use App\Challenges\Classes\TierListConstructionPhase;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use App\Modifiers\Classes\TierListModifier;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [TierListConstructionPhase::key()],
            'duration' => null,
        ],
    ];

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'individual',
        modifiers: [TierListModifier::key()]
    );

    $this->createGame();

    $this->player_1 = $this->createPlayer();
    $this->player_2 = $this->createPlayer();
    $this->player_3 = $this->createPlayer();
    $this->player_4 = $this->createPlayer();

    $this->game->start();

    $this->construction_challenge = $this->game->fresh()->challenges->first();
});

it('allows challenges that have no duration to be ended', function () {
    expect($this->construction_challenge->starts_at)->not()->toBeNull();
    expect($this->construction_challenge->ends_at)->toBeNull();
});

it('selects 3 categories', function () {
    expect($this->construction_challenge->challenge_data['categories'])->toHaveCount(3);
});

it('requires 5 submissions per player per category', function () {
    $key = TierListConstructionPhase::key();
    $category = $this->construction_challenge->challenge_data['categories'][0];

    Livewire::actingAs($this->player_1->fresh()->user)
        ->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.' . $key . '.' . $category . '-A', 'Candy A')
        ->set('round_properties.' . $key . '.' . $category . '-B', 'Candy B')
        ->set('round_properties.' . $key . '.' . $category . '-C', 'Candy C')
        ->set('round_properties.' . $key . '.' . $category . '-D', 'Candy D')
        // fail to include F tier
        // ->set('round_properties.' . $key . '.' . $category . '-F', 'Candy F')
        ->call('callClassAction', 'submitTierList', 'challenge', $key)
        ->assertHasErrors();

    $this->construction_challenge->refresh();
});

it('does not allow two identical submissions per player per category', function () {
    $key = TierListConstructionPhase::key();
    $category = $this->construction_challenge->challenge_data['categories'][0];

    Livewire::actingAs($this->player_1->fresh()->user)
        ->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.' . $key . '.' . $category . '-A', 'Candy A')
        ->set('round_properties.' . $key . '.' . $category . '-B', 'Candy B')
        ->set('round_properties.' . $key . '.' . $category . '-C', 'Candy C')
        ->set('round_properties.' . $key . '.' . $category . '-D', 'Candy F')
        ->set('round_properties.' . $key . '.' . $category . '-F', 'Candy F')
        ->call('callClassAction', 'submitTierList', 'challenge', $key)
        ->assertHasErrors();

    $this->construction_challenge->refresh();
});

it('automatically ends the challenge when all players have submitted', function () {
    $key = TierListConstructionPhase::key();
    $category_0 = $this->construction_challenge->challenge_data['categories'][0];
    $category_1 = $this->construction_challenge->challenge_data['categories'][1];
    $category_2 = $this->construction_challenge->challenge_data['categories'][2];

    $this->game->players->each(function ($player) use ($key, $category_0, $category_1, $category_2) {
        Livewire::actingAs($player->fresh()->user)
            ->test(GameDashboard::class, ['game' => $this->game->fresh()])
            ->set('round_properties.' . $key . '.' . $category_0 . '-A', 'Candy A')
            ->set('round_properties.' . $key . '.' . $category_0 . '-B', 'Candy B')
            ->set('round_properties.' . $key . '.' . $category_0 . '-C', 'Candy C')
            ->set('round_properties.' . $key . '.' . $category_0 . '-D', 'Candy D')
            ->set('round_properties.' . $key . '.' . $category_0 . '-F', 'Candy F')
            ->call('callClassAction', 'submitTierList', 'challenge', $key)
            ->assertHasNoErrors();

        Livewire::actingAs($player->fresh()->user)
            ->test(GameDashboard::class, ['game' => $this->game->fresh()])
            ->set('round_properties.' . $key . '.' . $category_1 . '-A', 'Candy A')
            ->set('round_properties.' . $key . '.' . $category_1 . '-B', 'Candy B')
            ->set('round_properties.' . $key . '.' . $category_1 . '-C', 'Candy C')
            ->set('round_properties.' . $key . '.' . $category_1 . '-D', 'Candy D')
            ->set('round_properties.' . $key . '.' . $category_1 . '-F', 'Candy F')
            ->call('callClassAction', 'submitTierList', 'challenge', $key)
            ->assertHasNoErrors();

        Livewire::actingAs($player->fresh()->user)
            ->test(GameDashboard::class, ['game' => $this->game->fresh()])
            ->set('round_properties.' . $key . '.' . $category_2 . '-A', 'Candy A')
            ->set('round_properties.' . $key . '.' . $category_2 . '-B', 'Candy B')
            ->set('round_properties.' . $key . '.' . $category_2 . '-C', 'Candy C')
            ->set('round_properties.' . $key . '.' . $category_2 . '-D', 'Candy D')
            ->set('round_properties.' . $key . '.' . $category_2 . '-F', 'Candy F')
            ->call('callClassAction', 'submitTierList', 'challenge', $key)
            ->assertHasNoErrors();
    });

    $this->construction_challenge->refresh();
    expect($this->construction_challenge->status)->toBe('ended');
});