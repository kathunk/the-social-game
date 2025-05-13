<?php

use Livewire\Livewire;
use App\Models\Challenge;
use Thunk\Verbs\Facades\Verbs;
use App\Livewire\GameDashboard;
use App\Events\PlayerSubmittedQuizGuess;
use App\Challenges\Classes\IndividualMostHiddenPointQuiz;

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

    incrementScore(player: $player_1, points: 10);
    incrementScore(player: $player_1, points: 10, is_hidden: true);
    incrementScore(player: $player_2, points: 20);
    incrementScore(player: $player_2, points: 10, is_hidden: true);
    incrementScore(player: $player_3, points: 30);
    incrementScore(player: $player_3, points: 9, is_hidden: true);
    incrementScore(player: $player_4, points: 40);

    $this->actingAs($player_1->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('challenge_properties.guess_player_id', $player_1->id)
        ->call('callChallengeAction', 'guess');

    $this->actingAs($player_2->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('challenge_properties.guess_player_id', $player_2->id)
        ->call('callChallengeAction', 'guess');

    $this->actingAs($player_3->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('challenge_properties.guess_player_id', $player_3->id)
        ->call('callChallengeAction', 'guess');

    $challenge->refresh();
    $challenge->end();

    // visible scores show this
    expect($player_1->fresh()->score)->toBe(10);
    expect($player_2->fresh()->score)->toBe(20);
    expect($player_3->fresh()->score)->toBe(30);
    expect($player_4->fresh()->score)->toBe(40);

    // but with hidden scores included, we see this
    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(21);
    expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(31);
    expect($player_3->fresh()->state()->score(include_hidden: true))->toBe(39);
    expect($player_4->fresh()->state()->score(include_hidden: true))->toBe(40);
});
