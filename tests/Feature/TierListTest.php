<?php

use App\Models\Game;
use Livewire\Livewire;
use App\Models\Challenge;
use Thunk\Verbs\Facades\Verbs;
use App\Livewire\GameDashboard;
use App\Modifiers\Classes\TierListModifier;
use App\Challenges\Classes\IndividualHighScoreQuiz;
use App\Challenges\Classes\TierListConstructionPhase;

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
    $this->player_5 = $this->createPlayer();
    $this->player_6 = $this->createPlayer();
    $this->player_7 = $this->createPlayer();
    $this->player_8 = $this->createPlayer();
    $this->player_9 = $this->createPlayer();
    $this->player_10 = $this->createPlayer();

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
    submitTierLists($this->game, $this->construction_challenge);

    $this->construction_challenge->refresh();
    expect($this->construction_challenge->status)->toBe('ended');
});

it('sets clues to be guessed on future rounds', function () {
    submitTierLists($this->game, $this->construction_challenge);

    $this->construction_challenge->fresh()->end();

    dd($this->game->players->pluck('name'), $this->game->fresh()->modifiers->first()->modifier_data['answer_keys']);
});

function submitTierLists(Game $game, Challenge $construction_challenge)
{
    $key = TierListConstructionPhase::key();
    $category_0 = $construction_challenge->challenge_data['categories'][0];
    $category_1 = $construction_challenge->challenge_data['categories'][1];
    $category_2 = $construction_challenge->challenge_data['categories'][2];

    $game->players->each(function ($player) use ($game, $key, $category_0, $category_1, $category_2) {
        Livewire::actingAs($player->fresh()->user)
            ->test(GameDashboard::class, ['game' => $game->fresh()])
            ->set('round_properties.' . $key . '.' . $category_0 . '-A', $player->name . '-' . $category_0 . '-A')
            ->set('round_properties.' . $key . '.' . $category_0 . '-B', $player->name . '-' . $category_0 . '-B')
            ->set('round_properties.' . $key . '.' . $category_0 . '-C', $player->name . '-' . $category_0 . '-C')
            ->set('round_properties.' . $key . '.' . $category_0 . '-D', $player->name . '-' . $category_0 . '-D')
            ->set('round_properties.' . $key . '.' . $category_0 . '-F', $player->name . '-' . $category_0 . '-F')
            ->call('callClassAction', 'submitTierList', 'challenge', $key);
            // ->assertHasNoErrors();

        Livewire::actingAs($player->fresh()->user)
            ->test(GameDashboard::class, ['game' => $game->fresh()])
            ->set('round_properties.' . $key . '.' . $category_1 . '-A', $player->name . '-' . $category_1 . '-A')
            ->set('round_properties.' . $key . '.' . $category_1 . '-B', $player->name . '-' . $category_1 . '-B')
            ->set('round_properties.' . $key . '.' . $category_1 . '-C', $player->name . '-' . $category_1 . '-C')
            ->set('round_properties.' . $key . '.' . $category_1 . '-D', $player->name . '-' . $category_1 . '-D')
            ->set('round_properties.' . $key . '.' . $category_1 . '-F', $player->name . '-' . $category_1 . '-F')
            ->call('callClassAction', 'submitTierList', 'challenge', $key);
            // ->assertHasNoErrors();

        Livewire::actingAs($player->fresh()->user)
            ->test(GameDashboard::class, ['game' => $game->fresh()])
            ->set('round_properties.' . $key . '.' . $category_2 . '-A', $player->name . '-' . $category_2 . '-A')
            ->set('round_properties.' . $key . '.' . $category_2 . '-B', $player->name . '-' . $category_2 . '-B')
            ->set('round_properties.' . $key . '.' . $category_2 . '-C', $player->name . '-' . $category_2 . '-C')
            ->set('round_properties.' . $key . '.' . $category_2 . '-D', $player->name . '-' . $category_2 . '-D')
            ->set('round_properties.' . $key . '.' . $category_2 . '-F', $player->name . '-' . $category_2 . '-F')
            ->call('callClassAction', 'submitTierList', 'challenge', $key);
            // ->assertHasNoErrors();
    });
}