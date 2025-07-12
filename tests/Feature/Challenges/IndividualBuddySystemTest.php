<?php

use App\Challenges\Classes\IndividualBuddySystem;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs individual buddy system', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualBuddySystem::key()],
            'duration' => 10,
        ],
    ];

    $this->mockGameTemplate(challenges: $challenges, type: 'individual');

    $this->createGame();

    $player_1 = $this->createPlayer();
    $player_2 = $this->createPlayer();
    $player_3 = $this->createPlayer();
    $player_4 = $this->createPlayer();

    $this->game->start();

    $challenge = Challenge::first();

    $this->actingAs($player_1->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set(
            'round_properties.'.$challenge->class_key.'.upvote_player_id',
            $player_2->id
        )
        ->set(
            'round_properties.'.$challenge->class_key.'.downvote_player_id',
            $player_3->id
        )
        ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)
        ->assertHasNoErrors();

    $this->actingAs($player_2->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set(
            'round_properties.'.$challenge->class_key.'.upvote_player_id',
            $player_1->id
        )
        ->set(
            'round_properties.'.$challenge->class_key.'.downvote_player_id',
            $player_4->id
        )
        ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)
        ->assertHasNoErrors();

    $this->actingAs($player_3->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set(
            'round_properties.'.$challenge->class_key.'.upvote_player_id',
            $player_4->id
        )
        ->set(
            'round_properties.'.$challenge->class_key.'.downvote_player_id',
            $player_1->id
        )
        ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)
        ->assertHasNoErrors();

    $challenge->refresh();
    $challenge->end();

    // visible scores show this
    expect($player_1->fresh()->score)->toBe(0);
    expect($player_2->fresh()->score)->toBe(1);
    expect($player_3->fresh()->score)->toBe(-1);
    expect($player_4->fresh()->score)->toBe(0);

    // but with hidden scores included, we see this
    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(1);
    expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(2);
    expect($player_3->fresh()->state()->score(include_hidden: true))->toBe(-1);
    expect($player_4->fresh()->state()->score(include_hidden: true))->toBe(0);
});
