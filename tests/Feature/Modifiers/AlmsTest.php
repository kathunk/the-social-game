<?php

use App\Challenges\Classes\PeckingOrder\IndividualChoosePointsOrHidden;
use App\Challenges\Classes\IndividualFiller;
use App\Livewire\GameDashboard;
use App\Modifiers\Classes\PeckingOrder\Alms;
use Illuminate\Support\Facades\Date;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('facilitates alms', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualFiller::key()],
            'duration' => 10,
        ],
        [
            'challenge_keys' => [IndividualFiller::key()],
            'duration' => 10,
        ],
        [
            'challenge_keys' => [IndividualChoosePointsOrHidden::key()],
            'duration' => 10,
        ],
    ];

    $modifiers = [Alms::key()];

    $this->mockGameTemplate(challenges: $challenges, type: 'individual', modifiers: $modifiers);

    $game = $this->createGame();
    $player_1 = $this->game->players->first();
    $player_2 = $this->createPlayer();
    $player_3 = $this->createPlayer();
    $player_4 = $this->createPlayer();
    $game->start();

    incrementScore(player: $player_1, points: 10);

    $end = $this->game->fresh()->currentChallenge->ends_at;
    Date::setTestNow($end->addSeconds(1));
    $this->artisan('app:progress-games');

    // applies to ALL players at the bottom of the scoreboard
    expect($player_1->fresh()->score)->toBe(10);
    expect($player_1->fresh()->hidden_score)->toBe(10);
    expect($player_2->fresh()->score)->toBe(0);
    expect($player_2->fresh()->hidden_score)->toBe(1);
    expect($player_3->fresh()->score)->toBe(0);
    expect($player_3->fresh()->hidden_score)->toBe(1);
    expect($player_4->fresh()->score)->toBe(0);
    expect($player_4->fresh()->hidden_score)->toBe(1);

    incrementScore(player: $player_2, points: 10, is_hidden: true);

    $end = $this->game->fresh()->currentChallenge->ends_at;
    Date::setTestNow($end->addSeconds(1));
    $this->artisan('app:progress-games');

    expect($player_1->fresh()->score)->toBe(10);
    expect($player_1->fresh()->hidden_score)->toBe(10);
    expect($player_2->fresh()->score)->toBe(0);
    expect($player_2->fresh()->hidden_score)->toBe(11);
    expect($player_3->fresh()->score)->toBe(0);
    expect($player_3->fresh()->hidden_score)->toBe(2);
    expect($player_4->fresh()->score)->toBe(0);
    expect($player_4->fresh()->hidden_score)->toBe(2);

    Livewire::actingAs($player_3->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->call('callClassAction', 'choose_points', 'challenge', IndividualChoosePointsOrHidden::key())
        ->assertHasNoErrors();

    $end = $this->game->fresh()->currentChallenge->ends_at;
    Date::setTestNow($end->addSeconds(1));
    $this->artisan('app:progress-games');

    // applies after the challenge is resolved. Player 2 gained 8 real points, and no hidden points
    expect($player_1->fresh()->score)->toBe(10);
    expect($player_1->fresh()->hidden_score)->toBe(10);
    expect($player_2->fresh()->score)->toBe(0);
    expect($player_2->fresh()->hidden_score)->toBe(11);
    expect($player_3->fresh()->score)->toBe(8);
    expect($player_3->fresh()->hidden_score)->toBe(10);
    expect($player_4->fresh()->score)->toBe(0);
    expect($player_4->fresh()->hidden_score)->toBe(3);
});
