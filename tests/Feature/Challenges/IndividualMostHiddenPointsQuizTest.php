<?php

use App\Challenges\Classes\PeckingOrder\IndividualMostHiddenPointQuiz;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs the individual most hidden points quiz', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualMostHiddenPointQuiz::key()],
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

    Livewire::actingAs($player_1->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.$challenge->class_key.'.upvote_player_id', $player_2->id)
        ->set('round_properties.'.$challenge->class_key.'.downvote_player_id', $player_3->id)
        ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)->assertHasNoErrors()
        ->set('round_properties.'.$challenge->class_key.'.guess_player_id', $player_1->id)
        ->call('callClassAction', 'guess', 'challenge', $challenge->class_key)->assertHasNoErrors();

    Livewire::actingAs($player_2->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.$challenge->class_key.'.upvote_player_id', $player_1->id)
        ->set('round_properties.'.$challenge->class_key.'.downvote_player_id', $player_4->id)
        ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)->assertHasNoErrors()
        ->set('round_properties.'.$challenge->class_key.'.guess_player_id', $player_2->id)
        ->call('callClassAction', 'guess', 'challenge', $challenge->class_key)->assertHasNoErrors()
        ->call('callClassAction', 'go_invisible', 'challenge', $challenge->class_key)->assertHasNoErrors();

    Livewire::actingAs($player_3->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.$challenge->class_key.'.upvote_player_id', $player_2->id)
        ->set('round_properties.'.$challenge->class_key.'.downvote_player_id', $player_4->id)
        ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)->assertHasNoErrors();

    Livewire::actingAs($player_4->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.$challenge->class_key.'.upvote_player_id', $player_1->id)
        ->set('round_properties.'.$challenge->class_key.'.downvote_player_id', $player_2->id)
        ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)->assertHasNoErrors();

    $challenge->refresh();
    $challenge->end();

    // player 2 is invisible, so they get no public points
    expect($player_1->fresh()->score)->toBe(2);
    expect($player_2->fresh()->score)->toBe(0);
    expect($player_3->fresh()->score)->toBe(-1);
    expect($player_4->fresh()->score)->toBe(-2);

    // player 1 is wrong on the quiz, even though they have the most normal points
    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(2);
    // player 2 gets an extra hidden point for being right on the quiz
    expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(2);
    expect($player_3->fresh()->state()->score(include_hidden: true))->toBe(-1);
    expect($player_4->fresh()->state()->score(include_hidden: true))->toBe(-2);
});
