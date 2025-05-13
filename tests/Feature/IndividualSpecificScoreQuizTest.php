<?php

use App\Challenges\Classes\IndividualSpecificScoreQuiz;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs the individual guess specific score quiz', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualSpecificScoreQuiz::key()],
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
        ->set('challenge_properties.guess_score', 1)
        ->call('callChallengeAction', 'guess');

    $this->actingAs($player_2->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('challenge_properties.guess_score', -1)
        ->call('callChallengeAction', 'guess');

    $this->actingAs($player_3->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('challenge_properties.guess_score', 0)
        ->call('callChallengeAction', 'guess');

    $this->actingAs($player_4->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('challenge_properties.guess_score', -2)
        ->call('callChallengeAction', 'guess');

    $challenge->refresh();
    $challenge->end();

    // visible scores show this
    expect($player_1->fresh()->score)->toBe(0);
    expect($player_2->fresh()->score)->toBe(0);
    expect($player_3->fresh()->score)->toBe(0);
    expect($player_4->fresh()->score)->toBe(0);

    // but with hidden scores included, we see this
    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(1);
    expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(1);
    expect($player_3->fresh()->state()->score(include_hidden: true))->toBe(1);
    expect($player_4->fresh()->state()->score(include_hidden: true))->toBe(0);
});
