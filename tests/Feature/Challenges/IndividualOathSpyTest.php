<?php

use App\Challenges\PeckingOrder\IndividualOathSpy;
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
            'challenge_keys' => [IndividualOathSpy::key()],
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
        ->call('callClassAction', 'buy_information', 'challenge', $challenge->class_key)->assertHasNoErrors();

    Livewire::actingAs($player_1->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.BloodOaths::key().'.oath_offer_id', $player_2->id)
        ->call('callClassAction', 'offer_blood_oath', 'modifier', BloodOaths::key());

    Livewire::actingAs($player_2->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.BloodOaths::key().'.oath_offer_id', $player_1->id)
        ->call('callClassAction', 'offer_blood_oath', 'modifier', BloodOaths::key());

    Livewire::actingAs($player_3->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->call('callClassAction', 'declare_oath_of_solitude', 'modifier', BloodOaths::key());

    Livewire::actingAs($player_2->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->call('callClassAction', 'buy_information', 'challenge', $challenge->class_key)->assertHasNoErrors();

    // -1 for buying information
    expect($player_1->fresh()->state()->score(include_hidden: true))->toBe(-1);
    expect($player_2->fresh()->state()->score(include_hidden: true))->toBe(-1);

    $challenge_data = $challenge->fresh()->challenge_data;

    expect($challenge_data['information_bought'][$player_1->id])->toContain('There are no blood oath players to spy on.');
    expect($challenge_data['information_bought'][$player_1->id])->toContain('No opponent is in an oath of solitude.');
    expect($challenge_data['information_bought'][$player_1->id])->toContain('has no Oath, with a true score of');
    expect($challenge_data['information_bought'][$player_2->id])->toContain($player_1->name.' is in a blood oath, with a true score of -1.');
    expect($challenge_data['information_bought'][$player_2->id])->toContain($player_3->name.' is in an oath of solitude, with a true score of 3.');
    expect($challenge_data['information_bought'][$player_2->id])->toContain('has no Oath, with a true score of');
});

it('does not run the individual oath quiz if the blood oaths modifier is not present', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualOathSpy::key()],
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
