<?php

use App\Challenges\Classes\IndividualHighScoreQuiz;
use App\Livewire\GameDashboard;
use App\Modifiers\Classes\BloodOaths;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('facilitates blood oaths', function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            'challenge_keys' => [IndividualHighScoreQuiz::key()],
            'duration' => 10,
        ],
    ];

    $modifiers = [BloodOaths::key()];

    $this->mockGameTemplate(challenges: $challenges, type: 'individual', modifiers: $modifiers);

    $game = $this->createGame();
    $player_1 = $this->createPlayer();
    $player_2 = $this->createPlayer();
    $player_3 = $this->createPlayer();
    $game->start();

    Livewire::actingAs($player_1->fresh()->user)->test(GameDashboard::class, ['game' => $game->fresh()])
        ->assertSee('Blood Oath')
        ->call('callClassAction', 'declare_oath_of_solitude', 'modifier', BloodOaths::key())
        ->assertHasNoErrors();

    $modifier = $this->game->modifiers->first();
    expect($modifier->modifier_data['lone_wolves'][0])->toBe($player_1->id);

    Livewire::actingAs($player_1->fresh()->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('You have made an oath of solitude')
        ->assertDontSee('If you offer an oath to a player');

    Livewire::actingAs($player_2->fresh()->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.BloodOaths::key().'.oath_offer_id', $player_3->id)
        ->call('callClassAction', 'offer_blood_oath', 'modifier', BloodOaths::key())
        ->assertHasNoErrors();

    $modifier->refresh();
    expect($modifier->modifier_data['offers'][$player_2->id])->toBe($player_3->id);
    expect(collect($modifier->modifier_data['pairs'])->count())->toBe(0);

    Livewire::actingAs($player_2->fresh()->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('Your current offer: '.$player_3->name)
        ->assertDontSee('Players currently offering you an oath');

    Livewire::actingAs($player_3->fresh()->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('Players currently offering you an oath: '.$player_2->name)
        ->set('round_properties.'.BloodOaths::key().'.oath_offer_id', $player_2->id)
        ->call('callClassAction', 'offer_blood_oath', 'modifier', BloodOaths::key())
        ->assertHasNoErrors();

    $modifier->refresh();
    expect($modifier->modifier_data['pairs'][$player_2->id])->toBe($player_3->id);
    expect($modifier->modifier_data['pairs'][$player_3->id])->toBe($player_2->id);

    expect($modifier->modifier_data['offers'][$player_2->id])->toBe(null);
    expect($modifier->modifier_data['offers'][$player_3->id])->toBe(null);

    Livewire::actingAs($player_2->fresh()->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('You are in a blood oath with '.$player_3->name)
        ->assertDontSee('If you offer an oath');

    Livewire::actingAs($player_3->fresh()->user)->test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('You are in a blood oath with '.$player_2->name)
        ->assertDontSee('If you offer an oath');
});
