<?php

use App\Challenges\MorningRoutine\MorningRoutineRound;
use App\Challenges\MorningRoutine\Rewards\RewardRegistry;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use App\States\ChallengeState;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

/**
 * Helper: directly mutate ChallengeState's challenge_data and persist to model.
 * Lets tests set up scenarios that can't easily be reached via real events
 * (e.g. forcing specific available rewards or seeding mess).
 */
function mutateState(Challenge $challenge, callable $mutator): Challenge
{
    $state = ChallengeState::load($challenge->id);
    $mutator($state);
    Challenge::find($challenge->id)?->update(['challenge_data' => $state->challenge_data]);

    return $challenge->fresh();
}

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function setupMorningRoutine($test, int $player_count = 3): array
{
    Verbs::commitImmediately();

    $test->mockGameTemplate(
        challenges: [['challenge_keys' => [MorningRoutineRound::key()], 'duration' => 5]],
        type: 'individual',
    );

    $test->createGame();

    $players = collect();
    for ($i = 0; $i < $player_count; $i++) {
        $players->push($test->createPlayer());
    }

    $test->game->start();

    return [
        'players' => $players,
        'challenge' => Challenge::first(),
    ];
}

function callAction($test, $player, $challenge, string $action, array $props = [])
{
    $test->actingAs($player->user);

    $component = Livewire::test(GameDashboard::class, ['game' => $test->game->fresh()]);

    foreach ($props as $key => $value) {
        $component->set("round_properties.{$challenge->class_key}.{$key}", $value);
    }

    return $component->call('callClassAction', $action, 'challenge', $challenge->class_key);
}

it('initializes all players in the hallway with empty state', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this);

    $data = $challenge->challenge_data;

    foreach ($players as $p) {
        expect($data['player_locations'][$p->id])->toBe('hallway');
        expect($data['player_points'][$p->id])->toBe(0);
        expect($data['player_penalties'][$p->id])->toBe(0);
    }

    $desired_count = $challenge->game->players->count() + 2;

    foreach (MorningRoutineRound::ROOMS as $room) {
        $room_pool_size = count(RewardRegistry::forRoom($room));
        expect($data['room_mess'][$room])->toBe(0);
        expect($data['room_queues'][$room])->toBeNull();
        expect($data['available_rewards'][$room])->toHaveCount(min($desired_count, $room_pool_size));
    }
});

it('allows a player to enter an empty room', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this, 2);

    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'kitchen'])
        ->assertHasNoErrors();

    $challenge->refresh();
    expect($challenge->challenge_data['player_locations'][$players[0]->id])->toBe('kitchen');
});

it('prevents entering an occupied room', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this, 2);

    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'bathroom'])->assertHasNoErrors();
    callAction($this, $players[1], $challenge, 'enterRoom', ['room' => 'bathroom'])->assertHasErrors('action_error');

    $challenge->refresh();
    expect($challenge->challenge_data['player_locations'][$players[1]->id])->toBe('hallway');
});

it('allows a player to take a reward and earn points + add mess', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this, 2);

    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'bathroom'])->assertHasNoErrors();

    $challenge->refresh();
    $available = $challenge->challenge_data['available_rewards']['bathroom'];
    $reward_key = $available[0];
    $reward = RewardRegistry::find($reward_key);

    callAction($this, $players[0], $challenge, 'takeReward', ['reward_key' => $reward_key])
        ->assertHasNoErrors();

    $challenge->refresh();
    expect($challenge->challenge_data['taken_rewards']['bathroom'][$reward_key])->toBe($players[0]->id);
    expect($challenge->challenge_data['player_points'][$players[0]->id])->toBe($reward->points);
    // mess may differ if the reward has an onTaken effect (e.g. hand_sanitizer reduces mess)
    // so just verify mess >= 0
    expect($challenge->challenge_data['room_mess']['bathroom'])->toBeGreaterThanOrEqual(0);
});

it('prevents taking a second reward in the same room', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this, 2);

    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'study'])->assertHasNoErrors();

    $challenge->refresh();
    $available = $challenge->challenge_data['available_rewards']['study'];

    callAction($this, $players[0], $challenge, 'takeReward', ['reward_key' => $available[0]])
        ->assertHasNoErrors();

    callAction($this, $players[0], $challenge, 'takeReward', ['reward_key' => $available[1]])
        ->assertHasErrors('action_error');
});

it('allows queueing for an occupied room and auto-enters when door opens', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this, 2);

    // Player 0 enters bathroom
    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'bathroom'])->assertHasNoErrors();

    // Player 1 queues
    callAction($this, $players[1], $challenge, 'queueForRoom', ['room' => 'bathroom'])->assertHasNoErrors();

    $challenge->refresh();
    expect($challenge->challenge_data['room_queues']['bathroom'])->toBe($players[1]->id);

    // Player 0 exits → player 1 should auto-enter
    callAction($this, $players[0], $challenge, 'exitRoom')->assertHasNoErrors();

    $challenge->refresh();
    expect($challenge->challenge_data['player_locations'][$players[0]->id])->toBe('hallway');
    expect($challenge->challenge_data['player_locations'][$players[1]->id])->toBe('bathroom');
    expect($challenge->challenge_data['room_queues']['bathroom'])->toBeNull();
});

it('busts a player who exits a messy room with someone queued', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this, 2);

    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'kitchen'])->assertHasNoErrors();

    // Seed kitchen mess directly
    mutateState($challenge, fn ($state) => $state->challenge_data['room_mess']['kitchen'] = 5);

    callAction($this, $players[1], $challenge, 'queueForRoom', ['room' => 'kitchen'])->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'exitRoom')->assertHasNoErrors();

    $challenge->refresh();
    expect($challenge->challenge_data['player_penalties'][$players[0]->id])->toBe(5);
    expect($challenge->challenge_data['room_mess']['kitchen'])->toBe(0);
    expect($challenge->challenge_data['toasts'][$players[0]->id])->not->toBeEmpty();
    expect($challenge->challenge_data['toasts'][$players[1]->id])->not->toBeEmpty();
});

it('does not bust a player exiting a clean room with queue', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this, 2);

    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'study'])->assertHasNoErrors();
    callAction($this, $players[1], $challenge, 'queueForRoom', ['room' => 'study'])->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'exitRoom')->assertHasNoErrors();

    $challenge->refresh();
    expect($challenge->challenge_data['player_penalties'][$players[0]->id])->toBe(0);
});

it('does not bust a player exiting a messy room with no queue', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this, 2);

    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'kitchen'])->assertHasNoErrors();

    mutateState($challenge, fn ($state) => $state->challenge_data['room_mess']['kitchen'] = 5);

    callAction($this, $players[0], $challenge, 'exitRoom')->assertHasNoErrors();

    $challenge->refresh();
    expect($challenge->challenge_data['player_penalties'][$players[0]->id])->toBe(0);
    expect($challenge->challenge_data['room_mess']['kitchen'])->toBe(5);
});

it('cleans mess based on elapsed time', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this, 2);

    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'laundry'])->assertHasNoErrors();

    mutateState($challenge, fn ($state) => $state->challenge_data['room_mess']['laundry'] = 10);

    callAction($this, $players[0], $challenge, 'startCleaning')->assertHasNoErrors();

    // Rewind started_at by 30s to simulate 30s of cleaning
    mutateState($challenge, fn ($state) => $state->challenge_data['cleaning_state'][$players[0]->id]['started_at'] -= 30);

    callAction($this, $players[0], $challenge, 'stopCleaning')->assertHasNoErrors();

    $challenge->refresh();
    // 30 seconds / 15 seconds per mess = 2 mess removed
    expect($challenge->challenge_data['room_mess']['laundry'])->toBe(8);
    expect($challenge->challenge_data['cleaning_state'])->toBeEmpty();
});

it('hand sanitizer effect removes mess from bathroom on take', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this, 2);

    mutateState($challenge, function ($state) {
        $state->challenge_data['available_rewards']['bathroom'] = ['hand_sanitizer'];
        $state->challenge_data['room_mess']['bathroom'] = 8;
    });

    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'bathroom'])->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'takeReward', ['reward_key' => 'hand_sanitizer'])->assertHasNoErrors();

    $challenge->refresh();
    // bathroom started at 8, hand_sanitizer adds 1 mess and removes 5 = 8 + 1 - 5 = 4
    expect($challenge->challenge_data['room_mess']['bathroom'])->toBe(4);
    expect($challenge->challenge_data['player_points'][$players[0]->id])->toBe(1);
});

it('boss suit effect cancels a bust penalty', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this, 2);

    mutateState($challenge, fn ($state) => $state->challenge_data['available_rewards']['laundry'] = ['boss_suit']);

    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'laundry'])->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'takeReward', ['reward_key' => 'boss_suit'])->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'exitRoom')->assertHasNoErrors();

    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'kitchen'])->assertHasNoErrors();

    mutateState($challenge, fn ($state) => $state->challenge_data['room_mess']['kitchen'] = 5);

    callAction($this, $players[1], $challenge, 'queueForRoom', ['room' => 'kitchen'])->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'exitRoom')->assertHasNoErrors();

    $challenge->refresh();
    expect($challenge->challenge_data['player_penalties'][$players[0]->id])->toBe(0);
});
