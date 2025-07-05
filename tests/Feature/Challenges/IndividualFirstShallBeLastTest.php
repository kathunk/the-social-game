<?php

use App\Challenges\Classes\IndividualFirstShallBeLast;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs individual steal the bacon', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualFirstShallBeLast::key()],
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
    $player_4 = $this->createPlayer();
    $player_5 = $this->createPlayer();
    $player_6 = $this->createPlayer();
    $player_7 = $this->createPlayer();

    $this->game->start();

    incrementScore(10, player: $player_1);
    incrementScore(10, player: $player_2);
    incrementScore(-10, player: $player_3);
    incrementScore(-10, player: $player_4);

    // gap: 20, player count: 8, expected reward: 14
    $expected_reward = 14;

    $challenge = Challenge::first();

    $this->game->fresh()->players->filter(fn ($p) => $p->id !== $player_6->id && $p->id !== $player_7->id)
        ->each(fn ($player) => Livewire::actingAs($player->user)
            ->test(GameDashboard::class, ['game' => $this->game->fresh()])
            ->set('round_properties.'.$challenge->class_key.'.upvote_player_id', $player_6->id)
            ->set('round_properties.'.$challenge->class_key.'.downvote_player_id', $player_7->id)
            ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)
            ->assertHasNoErrors()
        );

    Livewire::actingAs($player_6->user)
        ->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->call('callClassAction', 'buy_security', 'challenge', $challenge->class_key)
        ->assertHasNoErrors();

    Livewire::actingAs($player_7->user)
        ->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->call('callClassAction', 'buy_security', 'challenge', $challenge->class_key)
        ->assertHasNoErrors();

    $this->game->fresh()->challenges->first()->end();

    expect($player_1->fresh()->hidden_score)->toBe(10 - $expected_reward);
    expect($player_2->fresh()->hidden_score)->toBe(10 - $expected_reward);
    expect($player_3->fresh()->hidden_score)->toBe(-10 + $expected_reward);
    expect($player_4->fresh()->hidden_score)->toBe(-10 + $expected_reward);
    expect($player_5->fresh()->hidden_score)->toBe(0);

    // each bought security for -1 hidden point.
    // player 6 got 5 upvotes, player 7 got 5 downvotes
    // each should block 2 upvotes and 2 downvotes
    expect($player_6->fresh()->score)->toBe(3);
    expect($player_7->fresh()->score)->toBe(-3);
    expect($player_6->fresh()->hidden_score)->toBe(2);
    expect($player_7->fresh()->hidden_score)->toBe(-4);
});
