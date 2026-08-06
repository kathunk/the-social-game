<?php

use App\Challenges\ElephantInTheRoom\ElephantMatch;
use App\Events\ElephantInTheRoom\TileSlid;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use App\Modifiers\ElephantInTheRoom\ElephantRematch;
use App\States\ChallengeState;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * GameUpdatedForReverb broadcasts used to trigger a full page redirect;
 * refreshGame now refreshes the dashboard in place. These tests pin the new
 * contract: a live component picks up state that changed AFTER it mounted,
 * without navigating.
 */
function refreshTestSetup($test): array
{
    Verbs::commitImmediately();

    $test->mockGameTemplate(
        challenges: [['challenge_keys' => [ElephantMatch::key()], 'duration' => 20]],
        type: 'individual',
        modifiers: [ElephantRematch::key()],
        min_players: 2,
        max_players: 2,
        scoreboard_type: 'none',
    );

    $test->createGame();
    $test->createPlayer();
    $test->game->start();

    return [
        'players' => $test->game->players->sortBy('id')->values(),
        'challenge' => Challenge::first(),
    ];
}

it('refreshes the dashboard in place with the opponent\'s move, without redirecting', function () {
    ['players' => $players, 'challenge' => $challenge] = refreshTestSetup($this);

    // The waiting player's dashboard mounts BEFORE the opponent moves
    $this->actingAs($players[1]->user);
    $component = Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()]);

    expect($component->get('challenge_component')['elements'][0]['state']['last_seq'])->toBe(0);

    // The opponent's move lands server-side (as if from their own device)
    TileSlid::fire(
        game_id: $this->game->id,
        challenge_id: $challenge->id,
        actor_id: (string) $players[0]->id,
        entry_space: 1,
        direction: 'down',
        client_move_id: 'opponent-move',
    );

    // The broadcast handler refreshes in place — no navigation
    $component->call('refreshGame')->assertNoRedirect();

    $state = $component->get('challenge_component')['elements'][0]['state'];

    expect($state['last_seq'])->toBe(1);
    expect($state['board'][1])->toBe((string) $players[0]->id);
});

it('transitions to the post-game surface in place when the game ends', function () {
    ['players' => $players, 'challenge' => $challenge] = refreshTestSetup($this);
    $winner = (string) $players[0]->id;

    // The waiting player's dashboard mounts while the game is still active
    $this->actingAs($players[1]->user);
    $component = Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()]);

    // The opponent wins on their own device, which ends the challenge + game
    $state = ChallengeState::load($challenge->id);
    $state->challenge_data['victory_shape'] = 'square';
    $state->challenge_data['board'][2] = $winner;
    $state->challenge_data['board'][5] = $winner;
    $state->challenge_data['board'][6] = $winner;
    $state->challenge_data['hands'][$winner] = 5;
    $state->challenge_data['elephant_space'] = 16;
    Challenge::find($challenge->id)?->update(['challenge_data' => $state->challenge_data]);

    $this->actingAs($players[0]->user);
    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->set('round_properties.'.ElephantMatch::key().'.entry_space', 1)
        ->set('round_properties.'.ElephantMatch::key().'.direction', 'right')
        ->set('round_properties.'.ElephantMatch::key().'.client_move_id', 'winning-move')
        ->call('callClassAction', 'slideTile', 'challenge', ElephantMatch::key())
        ->assertHasNoErrors();

    expect($this->game->fresh()->status)->toBe('ended');

    // The loser's stale component refreshes straight into post-game state
    $this->actingAs($players[1]->user);
    $component->call('refreshGame')->assertNoRedirect();

    expect($component->get('challenge_component'))->toBeNull();

    $rematch_card = $component->get('modifier_components')[ElephantRematch::key()];

    expect($rematch_card['elements'][0]['type'])->toBe('elephant_rematch');
});

it('still redirects a player who was removed from the game', function () {
    ['players' => $players] = refreshTestSetup($this);

    $this->actingAs($players[1]->user);
    $component = Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()]);

    // Test shortcut: force the removal directly — the player computed
    // filters removed/rejected players out, leaving the dashboard playerless
    $players[1]->update(['status' => 'removed']);

    $component->call('refreshGame')
        ->assertRedirect(route('game-dashboard', ['game' => $this->game->id]));
});
