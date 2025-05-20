<?php

use App\Challenges\Classes\IndividualStealTheBacon;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs individual steal the bacon', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualStealTheBacon::key()],
            'duration' => 10,
        ],
        [
            'challenge_keys' => [IndividualStealTheBacon::key()],
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
        ->call('callChallengeAction', 'steal_the_bacon')->assertHasNoErrors();

    $this->actingAs($player_2->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->call('callChallengeAction', 'steal_the_bacon')->assertHasNoErrors();

    $this->actingAs($player_3->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->call('callChallengeAction', 'steal_the_bacon')->assertHasNoErrors();

    $this->actingAs($player_4->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->call('callChallengeAction', 'steal_the_bacon')->assertHasNoErrors();

    $challenge->refresh();
    $challenge->end();
    $challenge = $this->game->fresh()->challenges->skip(1)->first();
    $challenge->start();

    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(-1);
    expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(-1);
    expect($player_3->fresh()->state()->score(include_hidden: true))->toBe(-1);
    expect($player_4->fresh()->state()->score(include_hidden: true))->toBe(-1);

    $this->actingAs($player_1->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->call('callChallengeAction', 'steal_the_bacon')->assertHasNoErrors();

    $challenge->refresh();
    $challenge->end();

    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(1);
    expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(-1);
    expect($player_3->fresh()->state()->score(include_hidden: true))->toBe(-1);
    expect($player_4->fresh()->state()->score(include_hidden: true))->toBe(-1);
});
