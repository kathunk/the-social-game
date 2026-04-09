<?php

use App\Challenges\Classes\PeckingOrder\IndividualHighTrustEnvironment;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs the individual high trust environment challenge', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualHighTrustEnvironment::key()],
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
        ->set('round_properties.'.$challenge->class_key.'.upvote_player_id', $player_3->id)
        ->set('round_properties.'.$challenge->class_key.'.downvote_player_id', $player_2->id)
        ->call('callClassAction', 'vote', 'challenge', $challenge->class_key)->assertHasNoErrors();

    Livewire::actingAs($player_2->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.$challenge->class_key.'.tripled_player_id', $player_1->id)
        ->call('callClassAction', 'triple_vote', 'challenge', $challenge->class_key)->assertHasNoErrors()
        ->set('round_properties.'.$challenge->class_key.'.upvote_player_id', $player_3->id);

    Livewire::actingAs($player_3->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.$challenge->class_key.'.tripled_player_id', $player_1->id)
        ->call('callClassAction', 'triple_vote', 'challenge', $challenge->class_key)->assertHasNoErrors();

    $challenge->refresh();
    $challenge->end();

    // visible scores show this
    expect($player_1->fresh()->score)->toBe(0);
    expect($player_2->fresh()->score)->toBe(-6);
    expect($player_3->fresh()->score)->toBe(6);
    expect($player_4->fresh()->score)->toBe(0);
});
