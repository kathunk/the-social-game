<?php

use App\Challenges\PeckingOrder\IndividualOathQuiz;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use App\Modifiers\PeckingOrder\BloodOaths;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('runs the individual oath quiz', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualOathQuiz::key()],
            'duration' => 10,
        ],
    ];

    $this->mockGameTemplate(
        challenges: $challenges,
        type: 'individual',
        modifiers: [BloodOaths::key()],
    );

    $this->createGame();

    $player_1 = $this->createPlayer();
    $player_2 = $this->createPlayer();
    $player_3 = $this->createPlayer();
    $player_4 = $this->createPlayer();

    $this->game->start();

    $challenge = Challenge::first();

    Livewire::actingAs($player_1->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.BloodOaths::key().'.oath_offer_id', $player_2->id)
        ->call('callClassAction', 'offer_blood_oath', 'modifier', BloodOaths::key());

    Livewire::actingAs($player_2->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.BloodOaths::key().'.oath_offer_id', $player_1->id)
        ->call('callClassAction', 'offer_blood_oath', 'modifier', BloodOaths::key());

    Livewire::actingAs($player_3->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->call('callClassAction', 'declare_oath_of_solitude', 'modifier', BloodOaths::key());

    Livewire::actingAs($player_1->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.$challenge->class_key.'.guess_player_id', $player_3->id)
        ->set('round_properties.'.$challenge->class_key.'.oath_type', 'oath_of_solitude')
        ->call('callClassAction', 'guess', 'challenge', $challenge->class_key)->assertHasNoErrors();

    Livewire::actingAs($player_2->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.$challenge->class_key.'.guess_player_id', $player_3->id)
        ->set('round_properties.'.$challenge->class_key.'.oath_type', 'blood_oath')
        ->call('callClassAction', 'guess', 'challenge', $challenge->class_key)->assertHasNoErrors();

    Livewire::actingAs($player_3->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.$challenge->class_key.'.guess_player_id', $player_4->id)
        ->set('round_properties.'.$challenge->class_key.'.oath_type', 'oath_of_solitude')
        ->call('callClassAction', 'guess', 'challenge', $challenge->class_key)->assertHasNoErrors();

    Livewire::actingAs($player_4->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.$challenge->class_key.'.guess_player_id', $player_2->id)
        ->set('round_properties.'.$challenge->class_key.'.oath_type', 'blood_oath')
        ->call('callClassAction', 'guess', 'challenge', $challenge->class_key)->assertHasNoErrors();

    $challenge->refresh();
    $challenge->end();

    // correct on quiz
    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(1);

    // -1 for being accurately guessed by player 4
    expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(-1);

    // +3 for solitude -1 for accurate guess from player 1
    expect($player_3->fresh()->state()->score(include_hidden: true))->toBe(2);

    // +1 for accurate quiz
    expect($player_4->fresh()->state()->score(include_hidden: true))->toBe(1);
});

it('does not run the individual oath quiz if the blood oaths modifier is not present', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualOathQuiz::key()],
            'duration' => 10,
        ],
    ];

    expect(function () use ($challenges) {
        $this->mockGameTemplate(
            challenges: $challenges,
            type: 'individual',
        );
    })->toThrow(\Exception::class, 'Blood Oaths modifier is required to run this challenge');
});
