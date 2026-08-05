<?php

use App\Challenges\ElephantInTheRoom\ElephantMatch;
use App\Challenges\ElephantInTheRoom\Support\BoardLogic;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use App\States\ChallengeState;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Helper: directly mutate ChallengeState's challenge_data and persist to model.
 * Lets tests set up board positions that would take many moves to reach.
 */
function elephantMutateState(Challenge $challenge, callable $mutator): Challenge
{
    $state = ChallengeState::load($challenge->id);
    $mutator($state);
    Challenge::find($challenge->id)?->update(['challenge_data' => $state->challenge_data]);

    return $challenge->fresh();
}

/**
 * Boots a started game on the ElephantMatch challenge. With $bot_game the
 * game has a single human player (creator) and the virtual bot opponent;
 * otherwise exactly two human players.
 *
 * Returns players sorted by id — index 0 is the creator, who moves first.
 */
function setupElephantMatch($test, bool $bot_game = false): array
{
    Verbs::commitImmediately();

    $test->mockGameTemplate(
        challenges: [['challenge_keys' => [ElephantMatch::key()], 'duration' => 20]],
        type: 'individual',
        min_players: $bot_game ? 1 : 2,
        max_players: $bot_game ? 1 : 2,
    );

    $test->createGame();

    if (! $bot_game) {
        $test->createPlayer();
    }

    $test->game->start();

    return [
        'players' => $test->game->players->sortBy('id')->values(),
        'challenge' => Challenge::first(),
    ];
}

function elephantCallAction($test, $player, $challenge, string $action, array $props = [])
{
    $test->actingAs($player->user);

    $component = Livewire::test(GameDashboard::class, ['game' => $test->game->fresh()]);

    foreach ($props as $key => $value) {
        $component->set("round_properties.{$challenge->class_key}.{$key}", $value);
    }

    return $component->call('callClassAction', $action, 'challenge', $challenge->class_key);
}

// ─────────────────────────────────────────────────────────────────────────
// Setup & initial state
// ─────────────────────────────────────────────────────────────────────────

it('initializes a 2-player match with an empty board and the elephant on space 6', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);

    $data = $challenge->challenge_data;

    expect(collect($data['board'])->filter())->toBeEmpty();
    expect($data['elephant_space'])->toBe(6);
    expect($data['phase'])->toBe('tile');
    expect($data['is_bot_game'])->toBeFalse();
    expect($data['match_status'])->toBe('active');
    expect($data['actor_order'])->toBe([(string) $players[0]->id, (string) $players[1]->id]);
    expect($data['current_actor_id'])->toBe((string) $players[0]->id);
    expect($data['hands'][$players[0]->id])->toBe(8);
    expect($data['hands'][$players[1]->id])->toBe(8);
    expect($data['victory_shape'])->toBeIn(BoardLogic::SHAPES);
    expect($data['moves'])->toBe([]);
});

it('initializes a bot game with the virtual bot as the second actor', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this, bot_game: true);

    $data = $challenge->challenge_data;

    expect($data['is_bot_game'])->toBeTrue();
    expect($data['actor_order'])->toBe([(string) $players[0]->id, ElephantMatch::BOT_ID]);
    expect($data['hands'][ElephantMatch::BOT_ID])->toBe(8);
    expect($data['victory_shape'])->toBeIn(BoardLogic::BOT_SHAPES);
});

// ─────────────────────────────────────────────────────────────────────────
// Tile slides
// ─────────────────────────────────────────────────────────────────────────

it('lets the current player slide a tile onto an empty board', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);

    elephantCallAction($this, $players[0], $challenge, 'slideTile', [
        'entry_space' => 1,
        'direction' => 'down',
        'client_move_id' => 'move-1',
    ])->assertHasNoErrors();

    $data = $challenge->refresh()->challenge_data;

    expect($data['board'][1])->toBe((string) $players[0]->id);
    expect($data['hands'][$players[0]->id])->toBe(7);
    expect($data['phase'])->toBe('move');
    expect($data['moves'])->toHaveCount(1);
    expect($data['moves'][0]['seq'])->toBe(1);
    expect($data['moves'][0]['type'])->toBe('tile');
});

it('rejects a slide from the player whose turn it is not', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);

    elephantCallAction($this, $players[1], $challenge, 'slideTile', [
        'entry_space' => 1,
        'direction' => 'down',
        'client_move_id' => 'move-1',
    ])->assertHasErrors('action_error');

    expect(collect($challenge->refresh()->challenge_data['board'])->filter())->toBeEmpty();
});

it('rejects a second slide during the elephant phase', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);

    elephantCallAction($this, $players[0], $challenge, 'slideTile', [
        'entry_space' => 1, 'direction' => 'down', 'client_move_id' => 'move-1',
    ])->assertHasNoErrors();

    elephantCallAction($this, $players[0], $challenge, 'slideTile', [
        'entry_space' => 2, 'direction' => 'down', 'client_move_id' => 'move-2',
    ])->assertHasErrors('action_error');
});

it('rejects an invalid slide configuration', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);

    elephantCallAction($this, $players[0], $challenge, 'slideTile', [
        'entry_space' => 6, 'direction' => 'down', 'client_move_id' => 'move-1',
    ])->assertHasErrors('action_error');
});

it('rejects a slide when the elephant sits on the entry space', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);

    $challenge = elephantMutateState($challenge, function ($state) {
        $state->challenge_data['elephant_space'] = 1;
    });

    elephantCallAction($this, $players[0], $challenge, 'slideTile', [
        'entry_space' => 1, 'direction' => 'down', 'client_move_id' => 'move-1',
    ])->assertHasErrors('action_error');
});

it('rejects a slide when the elephant blocks the cascade path', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);

    // Entry 2 sliding down runs [2, 6, 10, 14]; a tile on 2 would need to
    // shift into 6, where the elephant sits
    $challenge = elephantMutateState($challenge, function ($state) use ($players) {
        $state->challenge_data['board'][2] = (string) $players[1]->id;
    });

    elephantCallAction($this, $players[0], $challenge, 'slideTile', [
        'entry_space' => 2, 'direction' => 'down', 'client_move_id' => 'move-1',
    ])->assertHasErrors('action_error');
});

it('cascades occupants and returns a pushed-off tile to its owner', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);
    $p1 = (string) $players[0]->id;
    $p2 = (string) $players[1]->id;

    // Row 1 full: sliding right from 1 pushes p2's tile at 4 off the board
    $challenge = elephantMutateState($challenge, function ($state) use ($p1, $p2) {
        $state->challenge_data['victory_shape'] = 'square';
        $state->challenge_data['board'][1] = $p1;
        $state->challenge_data['board'][2] = $p1;
        $state->challenge_data['board'][3] = $p1;
        $state->challenge_data['board'][4] = $p2;
        $state->challenge_data['hands'][$p1] = 5;
        $state->challenge_data['hands'][$p2] = 7;
        $state->challenge_data['elephant_space'] = 16;
    });

    elephantCallAction($this, $players[0], $challenge, 'slideTile', [
        'entry_space' => 1, 'direction' => 'right', 'client_move_id' => 'move-1',
    ])->assertHasNoErrors();

    $data = $challenge->refresh()->challenge_data;

    expect($data['board'][1])->toBe($p1); // the new tile
    expect($data['board'][2])->toBe($p1);
    expect($data['board'][3])->toBe($p1);
    expect($data['board'][4])->toBe($p1); // shifted from 3
    expect($data['hands'][$p1])->toBe(4);
    expect($data['hands'][$p2])->toBe(8); // pushed-off tile returned
    expect($data['moves'][0]['pushed_off_owner'])->toBe($p2);
    expect($data['match_status'])->toBe('active');
});

it('rejects a duplicate client_move_id', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);

    elephantCallAction($this, $players[0], $challenge, 'slideTile', [
        'entry_space' => 1, 'direction' => 'down', 'client_move_id' => 'same-id',
    ])->assertHasNoErrors();

    elephantCallAction($this, $players[0], $challenge, 'moveElephant', [
        'to_space' => 7, 'client_move_id' => 'same-id',
    ])->assertHasErrors('action_error');
});

// ─────────────────────────────────────────────────────────────────────────
// Elephant moves & turn passing
// ─────────────────────────────────────────────────────────────────────────

it('moves the elephant to an adjacent space and passes the turn', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);

    elephantCallAction($this, $players[0], $challenge, 'slideTile', [
        'entry_space' => 1, 'direction' => 'down', 'client_move_id' => 'move-1',
    ])->assertHasNoErrors();

    $before = $challenge->refresh()->challenge_data['turn_started_at'];

    elephantCallAction($this, $players[0], $challenge, 'moveElephant', [
        'to_space' => 7, 'client_move_id' => 'move-2',
    ])->assertHasNoErrors();

    $data = $challenge->refresh()->challenge_data;

    expect($data['elephant_space'])->toBe(7);
    expect($data['phase'])->toBe('tile');
    expect($data['current_actor_id'])->toBe((string) $players[1]->id);
    expect($data['turn_started_at'])->toBeGreaterThanOrEqual($before);
});

it('allows the elephant to stay in place', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);

    elephantCallAction($this, $players[0], $challenge, 'slideTile', [
        'entry_space' => 1, 'direction' => 'down', 'client_move_id' => 'move-1',
    ])->assertHasNoErrors();

    elephantCallAction($this, $players[0], $challenge, 'moveElephant', [
        'to_space' => 6, 'client_move_id' => 'move-2',
    ])->assertHasNoErrors();

    expect($challenge->refresh()->challenge_data['elephant_space'])->toBe(6);
});

it('rejects a non-adjacent elephant move', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);

    elephantCallAction($this, $players[0], $challenge, 'slideTile', [
        'entry_space' => 1, 'direction' => 'down', 'client_move_id' => 'move-1',
    ])->assertHasNoErrors();

    elephantCallAction($this, $players[0], $challenge, 'moveElephant', [
        'to_space' => 16, 'client_move_id' => 'move-2',
    ])->assertHasErrors('action_error');
});

it('rejects an elephant move during the tile phase', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);

    elephantCallAction($this, $players[0], $challenge, 'moveElephant', [
        'to_space' => 7, 'client_move_id' => 'move-1',
    ])->assertHasErrors('action_error');
});

it('keeps the turn when the opponent has no tiles in hand', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);
    $p2 = (string) $players[1]->id;

    $challenge = elephantMutateState($challenge, function ($state) use ($p2) {
        $state->challenge_data['hands'][$p2] = 0;
    });

    elephantCallAction($this, $players[0], $challenge, 'slideTile', [
        'entry_space' => 1, 'direction' => 'down', 'client_move_id' => 'move-1',
    ])->assertHasNoErrors();

    elephantCallAction($this, $players[0], $challenge, 'moveElephant', [
        'to_space' => 7, 'client_move_id' => 'move-2',
    ])->assertHasNoErrors();

    expect($challenge->refresh()->challenge_data['current_actor_id'])
        ->toBe((string) $players[0]->id);
});

// ─────────────────────────────────────────────────────────────────────────
// Victory & match end
// ─────────────────────────────────────────────────────────────────────────

it('ends the match, challenge, and game when a player completes their shape', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);
    $p1 = (string) $players[0]->id;

    // Square [1,2,5,6]: p1 holds 2, 5, 6 — sliding into the empty entry 1 wins
    $challenge = elephantMutateState($challenge, function ($state) use ($p1) {
        $state->challenge_data['victory_shape'] = 'square';
        $state->challenge_data['board'][2] = $p1;
        $state->challenge_data['board'][5] = $p1;
        $state->challenge_data['board'][6] = $p1;
        $state->challenge_data['hands'][$p1] = 5;
        $state->challenge_data['elephant_space'] = 16;
    });

    elephantCallAction($this, $players[0], $challenge, 'slideTile', [
        'entry_space' => 1, 'direction' => 'right', 'client_move_id' => 'move-1',
    ])->assertHasNoErrors();

    $data = $challenge->refresh()->challenge_data;

    expect($data['match_status'])->toBe('complete');
    expect($data['victor_ids'])->toBe([$p1]);
    expect($data['winning_spaces'])->toBe([1, 2, 5, 6]);

    // ProgressChallenge runs synchronously in tests: challenge + game end,
    // and the winner's point lands in their score history
    expect($challenge->refresh()->status)->toBe('ended');
    expect($this->game->fresh()->status)->toBe('ended');
    expect($players[0]->fresh()->score)->toBe(ElephantMatch::WIN_POINTS);
    expect($players[1]->fresh()->score)->toBe(0);
});

it('awards victory when the opponent\'s slide pushes your tiles into your shape', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);
    $p1 = (string) $players[0]->id;
    $p2 = (string) $players[1]->id;

    // Line [1,5,9,13]: p1 holds 5, 9, 13 plus a tile on 2. p2 slides left
    // from 16... no — from entry 4 (path [4,3,2,1]): the full cascade pushes
    // p1's tile from 2 into 1, completing p1's line on p2's own move.
    $challenge = elephantMutateState($challenge, function ($state) use ($p1, $p2) {
        $state->challenge_data['victory_shape'] = 'line';
        $state->challenge_data['board'][5] = $p1;
        $state->challenge_data['board'][9] = $p1;
        $state->challenge_data['board'][13] = $p1;
        $state->challenge_data['board'][2] = $p1;
        $state->challenge_data['board'][3] = $p2;
        $state->challenge_data['board'][4] = $p2;
        $state->challenge_data['hands'][$p1] = 4;
        $state->challenge_data['hands'][$p2] = 6;
        $state->challenge_data['current_actor_id'] = $p2;
        $state->challenge_data['elephant_space'] = 16;
    });

    elephantCallAction($this, $players[1], $challenge, 'slideTile', [
        'entry_space' => 4, 'direction' => 'left', 'client_move_id' => 'move-1',
    ])->assertHasNoErrors();

    $data = $challenge->refresh()->challenge_data;

    expect($data['match_status'])->toBe('complete');
    expect($data['victor_ids'])->toBe([$p1]);
    expect($players[0]->fresh()->score)->toBe(ElephantMatch::WIN_POINTS);
    expect($players[1]->fresh()->score)->toBe(0);
});

it('records both players as victors when one slide completes both shapes', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);
    $p1 = (string) $players[0]->id;
    $p2 = (string) $players[1]->id;

    // p2 slides down from entry 2 (path [2,6,10,14]): p2's tile at 2 shifts
    // to 6 completing p2's square [1,2,5,6], while p1's tile at 6 shifts to
    // 10 completing p1's square [10,11,14,15]. Both win — a draw.
    $challenge = elephantMutateState($challenge, function ($state) use ($p1, $p2) {
        $state->challenge_data['victory_shape'] = 'square';
        $state->challenge_data['board'][1] = $p2;
        $state->challenge_data['board'][5] = $p2;
        $state->challenge_data['board'][2] = $p2;
        $state->challenge_data['board'][6] = $p1;
        $state->challenge_data['board'][11] = $p1;
        $state->challenge_data['board'][14] = $p1;
        $state->challenge_data['board'][15] = $p1;
        $state->challenge_data['hands'][$p1] = 4;
        $state->challenge_data['hands'][$p2] = 5;
        $state->challenge_data['current_actor_id'] = $p2;
        $state->challenge_data['elephant_space'] = 16;
    });

    elephantCallAction($this, $players[1], $challenge, 'slideTile', [
        'entry_space' => 2, 'direction' => 'down', 'client_move_id' => 'move-1',
    ])->assertHasNoErrors();

    $data = $challenge->refresh()->challenge_data;

    expect($data['match_status'])->toBe('complete');
    expect(collect($data['victor_ids'])->sort()->values()->all())
        ->toBe(collect([$p1, $p2])->sort()->values()->all());
    expect($players[0]->fresh()->score)->toBe(ElephantMatch::WIN_POINTS);
    expect($players[1]->fresh()->score)->toBe(ElephantMatch::WIN_POINTS);
});

it('ends in a scoreless draw when both players run out of tiles', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);
    $p1 = (string) $players[0]->id;
    $p2 = (string) $players[1]->id;

    $challenge = elephantMutateState($challenge, function ($state) use ($p1, $p2) {
        $state->challenge_data['victory_shape'] = 'square';
        $state->challenge_data['hands'][$p1] = 1;
        $state->challenge_data['hands'][$p2] = 0;
    });

    elephantCallAction($this, $players[0], $challenge, 'slideTile', [
        'entry_space' => 1, 'direction' => 'down', 'client_move_id' => 'move-1',
    ])->assertHasNoErrors();

    $data = $challenge->refresh()->challenge_data;

    expect($data['match_status'])->toBe('complete');
    expect($data['victor_ids'])->toBe([]);
    expect($players[0]->fresh()->score)->toBe(0);
    expect($players[1]->fresh()->score)->toBe(0);
});

it('does not award points when the match clock runs out with no victor', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);

    $challenge->refresh()->end();
    Verbs::commit();

    expect($players[0]->fresh()->score)->toBe(0);
    expect($players[1]->fresh()->score)->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────
// Claim-forfeit
// ─────────────────────────────────────────────────────────────────────────

it('rejects a forfeit claim before the turn timer expires', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);

    elephantCallAction($this, $players[1], $challenge, 'claimForfeit')
        ->assertHasErrors('action_error');

    expect($challenge->refresh()->challenge_data['match_status'])->toBe('active');
});

it('rejects a forfeit claim from the player whose turn it is', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);

    $challenge = elephantMutateState($challenge, function ($state) {
        $state->challenge_data['turn_started_at'] = now()->timestamp - 120;
    });

    elephantCallAction($this, $players[0], $challenge, 'claimForfeit')
        ->assertHasErrors('action_error');
});

it('lets the waiting player claim the win after the turn timer expires', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);
    $p2 = (string) $players[1]->id;

    $challenge = elephantMutateState($challenge, function ($state) {
        $state->challenge_data['turn_started_at'] = now()->timestamp - 120;
    });

    elephantCallAction($this, $players[1], $challenge, 'claimForfeit')
        ->assertHasNoErrors();

    $data = $challenge->refresh()->challenge_data;

    expect($data['match_status'])->toBe('complete');
    expect($data['victor_ids'])->toBe([$p2]);
    expect($this->game->fresh()->status)->toBe('ended');
    expect($players[1]->fresh()->score)->toBe(ElephantMatch::WIN_POINTS);
    expect($players[0]->fresh()->score)->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────
// The bot turn
// ─────────────────────────────────────────────────────────────────────────

it('rejects playBotTurn in a 2-player game', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this);

    elephantCallAction($this, $players[0], $challenge, 'playBotTurn', [
        'bot_entry_space' => 1, 'bot_direction' => 'down',
        'bot_tile_move_id' => 'bot-1', 'bot_to_space' => 7, 'bot_elephant_move_id' => 'bot-2',
    ])->assertHasErrors('action_error');
});

it('rejects playBotTurn when it is not the bot\'s turn', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this, bot_game: true);

    elephantCallAction($this, $players[0], $challenge, 'playBotTurn', [
        'bot_entry_space' => 1, 'bot_direction' => 'down',
        'bot_tile_move_id' => 'bot-1', 'bot_to_space' => 7, 'bot_elephant_move_id' => 'bot-2',
    ])->assertHasErrors('action_error');
});

it('applies the bot\'s full turn (slide + elephant) and hands the turn back', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this, bot_game: true);
    $human = (string) $players[0]->id;

    elephantCallAction($this, $players[0], $challenge, 'slideTile', [
        'entry_space' => 1, 'direction' => 'down', 'client_move_id' => 'move-1',
    ])->assertHasNoErrors();

    elephantCallAction($this, $players[0], $challenge, 'moveElephant', [
        'to_space' => 7, 'client_move_id' => 'move-2',
    ])->assertHasNoErrors();

    expect($challenge->refresh()->challenge_data['current_actor_id'])->toBe(ElephantMatch::BOT_ID);

    elephantCallAction($this, $players[0], $challenge, 'playBotTurn', [
        'bot_entry_space' => 4, 'bot_direction' => 'down',
        'bot_tile_move_id' => 'bot-1', 'bot_to_space' => 6, 'bot_elephant_move_id' => 'bot-2',
    ])->assertHasNoErrors();

    $data = $challenge->refresh()->challenge_data;

    expect($data['board'][4])->toBe(ElephantMatch::BOT_ID);
    expect($data['hands'][ElephantMatch::BOT_ID])->toBe(7);
    expect($data['elephant_space'])->toBe(6);
    expect($data['current_actor_id'])->toBe($human);
    expect($data['phase'])->toBe('tile');
});

it('rejects an illegal bot slide', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this, bot_game: true);

    elephantCallAction($this, $players[0], $challenge, 'slideTile', [
        'entry_space' => 1, 'direction' => 'down', 'client_move_id' => 'move-1',
    ])->assertHasNoErrors();

    elephantCallAction($this, $players[0], $challenge, 'moveElephant', [
        'to_space' => 7, 'client_move_id' => 'move-2',
    ])->assertHasNoErrors();

    // Entry 7 is not a slide configuration at all
    elephantCallAction($this, $players[0], $challenge, 'playBotTurn', [
        'bot_entry_space' => 7, 'bot_direction' => 'down',
        'bot_tile_move_id' => 'bot-1', 'bot_to_space' => 6, 'bot_elephant_move_id' => 'bot-2',
    ])->assertHasErrors('action_error');
});

it('gives the human the win when their score is settled after beating the bot', function () {
    ['players' => $players, 'challenge' => $challenge] = setupElephantMatch($this, bot_game: true);
    $human = (string) $players[0]->id;

    $challenge = elephantMutateState($challenge, function ($state) use ($human) {
        $state->challenge_data['victory_shape'] = 'square';
        $state->challenge_data['board'][2] = $human;
        $state->challenge_data['board'][5] = $human;
        $state->challenge_data['board'][6] = $human;
        $state->challenge_data['hands'][$human] = 5;
        $state->challenge_data['elephant_space'] = 16;
    });

    elephantCallAction($this, $players[0], $challenge, 'slideTile', [
        'entry_space' => 1, 'direction' => 'right', 'client_move_id' => 'move-1',
    ])->assertHasNoErrors();

    $data = $challenge->refresh()->challenge_data;

    expect($data['match_status'])->toBe('complete');
    expect($data['victor_ids'])->toBe([$human]);
    expect($this->game->fresh()->status)->toBe('ended');
    expect($players[0]->fresh()->score)->toBe(ElephantMatch::WIN_POINTS);
});

// ─────────────────────────────────────────────────────────────────────────
// Victory table integrity
// ─────────────────────────────────────────────────────────────────────────

it('has the expected victory table sizes and well-formed sets', function () {
    $expected_counts = [
        'square' => 9,
        'line' => 8,
        'el' => 48,
        'zig' => 24,
        'pyramid' => 24,
    ];

    foreach ($expected_counts as $shape => $count) {
        $sets = BoardLogic::victorySets($shape);

        expect($sets)->toHaveCount($count);

        $unique = collect($sets)->map(fn ($set) => collect($set)->sort()->implode('-'))->unique();
        expect($unique)->toHaveCount($count);

        foreach ($sets as $set) {
            expect($set)->toHaveCount(4);
            expect(collect($set)->unique())->toHaveCount(4);
            foreach ($set as $space) {
                expect($space)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(16);
            }
        }
    }
});
