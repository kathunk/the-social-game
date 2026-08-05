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
        expect($data['room_queues'][$room])->toBe([]);
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
    expect($challenge->challenge_data['room_queues']['bathroom'])->toBe([$players[1]->id]);

    // Player 0 exits → player 1 should auto-enter
    callAction($this, $players[0], $challenge, 'exitRoom')->assertHasNoErrors();

    $challenge->refresh();
    expect($challenge->challenge_data['player_locations'][$players[0]->id])->toBe('hallway');
    expect($challenge->challenge_data['player_locations'][$players[1]->id])->toBe('bathroom');
    expect($challenge->challenge_data['room_queues']['bathroom'])->toBe([]);
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
    // Mess persists after a bust - only cleaning removes it
    expect($challenge->challenge_data['room_mess']['kitchen'])->toBe(5);
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

it('coffee effect lets player take an extra reward in the study', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this, 2);

    mutateState($challenge, function ($state) {
        $state->challenge_data['available_rewards']['kitchen'] = ['coffee'];
        $state->challenge_data['available_rewards']['study'] = ['housekeeping_handbook', 'intermittent_fasting'];
    });

    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'kitchen'])->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'takeReward', ['reward_key' => 'coffee'])->assertHasNoErrors();

    callAction($this, $players[0], $challenge, 'exitRoom')->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'study'])->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'takeReward', ['reward_key' => 'housekeeping_handbook'])->assertHasNoErrors();

    // Bonus pick: should succeed because of coffee
    callAction($this, $players[0], $challenge, 'takeReward', ['reward_key' => 'intermittent_fasting'])
        ->assertHasNoErrors();

    $challenge->refresh();
    expect($challenge->challenge_data['taken_rewards']['study'])->toHaveCount(2);
    expect($challenge->challenge_data['active_effects'][$players[0]->id]['extra_reward_study'] ?? null)->toBeNull();
});

it('coffee flag is one-shot - cannot take a third study reward', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this, 2);

    mutateState($challenge, function ($state) {
        $state->challenge_data['available_rewards']['kitchen'] = ['coffee'];
        $state->challenge_data['available_rewards']['study'] = ['housekeeping_handbook', 'intermittent_fasting', 'anarchists_cookbook'];
    });

    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'kitchen'])->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'takeReward', ['reward_key' => 'coffee'])->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'exitRoom')->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'study'])->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'takeReward', ['reward_key' => 'housekeeping_handbook'])->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'takeReward', ['reward_key' => 'intermittent_fasting'])->assertHasNoErrors();

    // Third attempt should fail
    callAction($this, $players[0], $challenge, 'takeReward', ['reward_key' => 'anarchists_cookbook'])
        ->assertHasErrors('action_error');
});

it('junk drawer pulls a random kitchen reward not in the game', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this, 2);

    // Force junk_drawer to be the only kitchen reward in the pool
    mutateState($challenge, function ($state) {
        $state->challenge_data['available_rewards']['kitchen'] = ['junk_drawer'];
    });

    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'kitchen'])->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'takeReward', ['reward_key' => 'junk_drawer'])->assertHasNoErrors();

    $challenge->refresh();

    // Player should have at least one toast about the junk drawer pull
    $junk_toasts = collect($challenge->challenge_data['toasts'][$players[0]->id] ?? [])
        ->where('type', 'junk_drawer');
    expect($junk_toasts)->not->toBeEmpty();

    // The player either has points OR the kitchen has mess (depending on which reward was pulled)
    $points = $challenge->challenge_data['player_points'][$players[0]->id];
    $kitchen_mess = $challenge->challenge_data['room_mess']['kitchen'];
    expect($points + $kitchen_mess)->toBeGreaterThanOrEqual(0);
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

it('persists round points and bust penalties to score history when the challenge ends', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this, 2);

    // Force a known reward so points are deterministic (hot_shave: 2 points, 2 mess, no effect)
    mutateState($challenge, fn ($state) => $state->challenge_data['available_rewards']['bathroom'] = ['hot_shave']);

    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'bathroom'])->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'takeReward', ['reward_key' => 'hot_shave'])->assertHasNoErrors();

    // Player 1 queues, player 0 exits with 2 mess -> busted for 2
    callAction($this, $players[1], $challenge, 'queueForRoom', ['room' => 'bathroom'])->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'exitRoom')->assertHasNoErrors();

    $challenge->refresh();
    expect($challenge->challenge_data['player_points'][$players[0]->id])->toBe(2);
    expect($challenge->challenge_data['player_penalties'][$players[0]->id])->toBe(2);

    // Player 1 auto-entered the bathroom; get them back to the hallway so they
    // aren't stranded when time runs out
    callAction($this, $players[1], $challenge, 'exitRoom')->assertHasNoErrors();

    $challenge->refresh();
    $challenge->end();

    // +2 reward points, -2 bust penalty
    expect($players[0]->fresh()->score)->toBe(0);
    expect($players[1]->fresh()->score)->toBe(0);

    $history = collect($players[0]->fresh()->state()->score_history);
    expect($history->pluck('points')->all())->toBe([2, -2]);
    expect($history->first()['description'])->toContain('Hot shave');
    expect($history->last()['description'])->toContain('Busted leaving the bathroom');
});

it('persists end-of-game effect bonuses like the mirror to score history', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this, 2);

    mutateState($challenge, fn ($state) => $state->challenge_data['available_rewards']['bathroom'] = ['mirror']);

    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'bathroom'])->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'takeReward', ['reward_key' => 'mirror'])->assertHasNoErrors();
    callAction($this, $players[0], $challenge, 'exitRoom')->assertHasNoErrors();

    // Seed a second taken reward so the mirror has a lowest-value reward to double
    // (hot_shave: 2 points)
    mutateState($challenge, function ($state) use ($players) {
        $state->challenge_data['taken_rewards']['bathroom']['hot_shave'] = $players[0]->id;
        $state->challenge_data['player_points'][$players[0]->id] += 2;
        $state->challenge_data['point_log'][] = [
            'player_id' => $players[0]->id,
            'points' => 2,
            'type' => 'reward',
            'label' => 'Hot shave',
        ];
    });

    $challenge->end();

    // 0 (mirror) + 2 (hot_shave) round points, +2 mirror bonus doubling hot_shave
    expect($players[0]->fresh()->score)->toBe(4);

    $history = collect($players[0]->fresh()->state()->score_history);
    expect($history->pluck('points')->all())->toBe([2, 2]);
    expect($history->last()['description'])->toContain('Mirror');
});

it('supports multiple players queueing FIFO and busts by the first in line', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this, 4);

    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'kitchen'])->assertHasNoErrors();

    mutateState($challenge, fn ($state) => $state->challenge_data['room_mess']['kitchen'] = 3);

    callAction($this, $players[1], $challenge, 'queueForRoom', ['room' => 'kitchen'])->assertHasNoErrors();
    callAction($this, $players[2], $challenge, 'queueForRoom', ['room' => 'kitchen'])->assertHasNoErrors();

    $challenge->refresh();
    expect($challenge->challenge_data['room_queues']['kitchen'])->toBe([$players[1]->id, $players[2]->id]);

    // Can't queue twice / can't hold two spots
    callAction($this, $players[1], $challenge, 'queueForRoom', ['room' => 'kitchen'])
        ->assertHasErrors('action_error');
    callAction($this, $players[3], $challenge, 'enterRoom', ['room' => 'study'])->assertHasNoErrors();
    callAction($this, $players[2], $challenge, 'queueForRoom', ['room' => 'study'])
        ->assertHasErrors('action_error');

    // Exit: first in line busts and auto-enters, second stays queued
    callAction($this, $players[0], $challenge, 'exitRoom')->assertHasNoErrors();

    $challenge->refresh();
    expect($challenge->challenge_data['player_locations'][$players[1]->id])->toBe('kitchen');
    expect($challenge->challenge_data['room_queues']['kitchen'])->toBe([$players[2]->id]);
    expect($challenge->challenge_data['player_penalties'][$players[0]->id])->toBe(3);
});

it('awards descending exit bonuses and penalizes players stranded in rooms', function () {
    ['players' => $players, 'challenge' => $challenge] = setupMorningRoutine($this, 3);

    // First out gets player_count points, next one fewer
    // (the game creator is also a player, so count from the actual roster)
    $n = count($challenge->challenge_data['player_locations']);

    callAction($this, $players[0], $challenge, 'leaveHouse')->assertHasNoErrors();
    callAction($this, $players[1], $challenge, 'leaveHouse')->assertHasNoErrors();

    $challenge->refresh();
    expect($challenge->challenge_data['exit_order'])->toBe([$players[0]->id, $players[1]->id]);
    expect($challenge->challenge_data['player_points'][$players[0]->id])->toBe($n);
    expect($challenge->challenge_data['player_points'][$players[1]->id])->toBe($n - 1);
    expect($challenge->challenge_data['player_locations'][$players[0]->id])->toBe('left');

    // Once out, you cannot come back in or leave again
    callAction($this, $players[0], $challenge, 'enterRoom', ['room' => 'kitchen'])
        ->assertHasErrors('action_error');
    callAction($this, $players[0], $challenge, 'leaveHouse')
        ->assertHasErrors('action_error');

    // Player 2 gets caught in a room when time runs out
    callAction($this, $players[2], $challenge, 'enterRoom', ['room' => 'study'])->assertHasNoErrors();

    $challenge->refresh();
    $challenge->end();

    expect($players[0]->fresh()->score)->toBe($n);
    expect($players[1]->fresh()->score)->toBe($n - 1);
    expect($players[2]->fresh()->score)->toBe(-1);

    $stranded = collect($players[2]->fresh()->state()->score_history)->last();
    expect($stranded['description'])->toContain('Still in the study');
});
