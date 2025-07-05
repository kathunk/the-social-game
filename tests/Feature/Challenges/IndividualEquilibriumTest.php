<?php

use App\Challenges\Classes\IndividualEquilibrium;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs the equilibrium challenge', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualEquilibrium::key()],
            'duration' => 10,
        ],
    ];

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'individual',
    );

    $this->createGame();

    $player_2 = $this->createPlayer();
    $player_3 = $this->createPlayer();
    $player_4 = $this->createPlayer();

    $this->game->start();

    // the average is 10
    incrementScore(30, player: $player_2);
    incrementScore(10, player: $player_3);
    incrementScore(0, player: $player_4);

    // and the average is not affected by hidden points
    incrementScore(100, player: $player_4, is_hidden: true);

    $challenge = Challenge::first();

    $this->actingAs($player_3->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('The average score (not including hidde points) is 10.')
        ->set('round_properties.'.$challenge->class_key.'.upvote_player_id', $player_2->id)
        ->set('round_properties.'.$challenge->class_key.'.downvote_player_id', $player_4->id)
        ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)
        ->assertHasErrors()
        ->set('round_properties.'.$challenge->class_key.'.upvote_player_id', $player_4->id)
        ->set('round_properties.'.$challenge->class_key.'.downvote_player_id', $player_4->id)
        ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)
        ->assertHasErrors()
        ->set('round_properties.'.$challenge->class_key.'.upvote_player_id', $player_4->id)
        ->set('round_properties.'.$challenge->class_key.'.downvote_player_id', $player_2->id)
        ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)
        ->assertHasNoErrors();

        $challenge->refresh();
        $challenge->end();
    
        // visible scores show this
        expect($player_2->fresh()->score)->toBe(29);
        expect($player_4->fresh()->score)->toBe(1);
    
        // but with hidden scores included, we see this
        expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(29);
        expect($player_4->fresh()->state()->score(include_hidden: true))->toBe(101);
});
