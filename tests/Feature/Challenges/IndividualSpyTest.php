<?php

use App\Challenges\Classes\IndividualSpy;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs the individual oath quiz', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualSpy::key()],
            'duration' => 10,
        ],
    ];

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'individual',
    );

    $this->createGame();

    $player_1 = $this->createPlayer();
    $player_2 = $this->createPlayer();
    $player_3 = $this->createPlayer();

    $this->game->start();

    $challenge = Challenge::first();

    incrementScore(1, player: $player_1, is_hidden: false);
    incrementScore(1, player: $player_1, is_hidden: true);
    incrementScore(2, player: $player_2, is_hidden: false);
    incrementScore(2, player: $player_2, is_hidden: true);
    incrementScore(3, player: $player_3, is_hidden: false);
    incrementScore(3, player: $player_3, is_hidden: true);

    $first_player = $this->game->players->first();

    Livewire::actingAs($first_player->user)
        ->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->call('callClassAction', 'buy_information', 'challenge', $challenge->class_key)
        ->assertHasNoErrors();

    // -1 for buying information
    expect($first_player->fresh()->state()->score(include_hidden: true))->toBe(-1);

    $challenge_data = $challenge->fresh()->challenge_data;

    expect($challenge_data['information_bought'][$first_player->id])->toContain($player_1->name.' has a hidden score of 2');
    expect($challenge_data['information_bought'][$first_player->id])->toContain($player_2->name.' has a hidden score of 4');
    expect($challenge_data['information_bought'][$first_player->id])->toContain($player_3->name.' has a hidden score of 6');
});
