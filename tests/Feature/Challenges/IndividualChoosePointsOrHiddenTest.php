<?php

use App\Challenges\Classes\PeckingOrder\IndividualChoosePointsOrHidden;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs individual choose points or hidden', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualChoosePointsOrHidden::key()],
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

    $this->game->start();

    $challenge = Challenge::first();

    $this->actingAs($player_1->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.$challenge->class_key.'.upvote_player_id', $player_3->id)
        ->set('round_properties.'.$challenge->class_key.'.downvote_player_id', $player_2->id)
        ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)->assertHasNoErrors()
        ->call('callClassAction', 'choose_points', 'challenge', $challenge->class_key)->assertHasNoErrors();

    $this->actingAs($player_2->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.$challenge->class_key.'.upvote_player_id', $player_4->id)
        ->set('round_properties.'.$challenge->class_key.'.downvote_player_id', $player_1->id)
        ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)->assertHasNoErrors()
        ->call('callClassAction', 'choose_points', 'challenge', $challenge->class_key)->assertHasNoErrors();

    $this->actingAs($player_3->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.$challenge->class_key.'.upvote_player_id', $player_4->id)
        ->set('round_properties.'.$challenge->class_key.'.downvote_player_id', $player_2->id)
        ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)->assertHasNoErrors()
        ->call('callClassAction', 'choose_points', 'challenge', $challenge->class_key)->assertHasNoErrors();

    $this->actingAs($player_4->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.$challenge->class_key.'.upvote_player_id', $player_3->id)
        ->set('round_properties.'.$challenge->class_key.'.downvote_player_id', $player_1->id)
        ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)->assertHasNoErrors()
        ->call('callClassAction', 'choose_hidden', 'challenge', $challenge->class_key)->assertHasNoErrors();

    $challenge->refresh();
    $challenge->end();

    // players 1, 2, and 3 split 10 points, rounded down: 3 points each

    // visible scores show this
    expect($player_1->fresh()->score)->toBe(1);
    expect($player_2->fresh()->score)->toBe(1);
    expect($player_3->fresh()->score)->toBe(5);
    expect($player_4->fresh()->score)->toBe(2);

    // player 4 got all 4 hidden points alone

    // but with hidden scores included, we see this
    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(1);
    expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(1);
    expect($player_3->fresh()->state()->score(include_hidden: true))->toBe(5);
    expect($player_4->fresh()->state()->score(include_hidden: true))->toBe(7);
});

it('handles a production error regression test', function () {
    // one player chooses hidden, one chooses regular

    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualChoosePointsOrHidden::key()],
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

    $this->game->start();

    $challenge = Challenge::first();

    $this->actingAs($player_1->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->call('callClassAction', 'choose_hidden', 'challenge', $challenge->class_key)->assertHasNoErrors();

    $this->actingAs($player_2->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->call('callClassAction', 'choose_points', 'challenge', $challenge->class_key)->assertHasNoErrors();

    $challenge->refresh();
    $challenge->end();

    // players 1, 2, and 3 split 10 points, rounded down: 3 points each

    // visible scores show this
    expect($player_1->fresh()->score)->toBe(0);
    expect($player_2->fresh()->score)->toBe(10);
    expect($player_3->fresh()->score)->toBe(0);
    expect($player_4->fresh()->score)->toBe(0);

    // player 4 got all 4 hidden points alone

    // but with hidden scores included, we see this
    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(5);
    expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(10);
    expect($player_3->fresh()->state()->score(include_hidden: true))->toBe(0);
    expect($player_4->fresh()->state()->score(include_hidden: true))->toBe(0);
});
