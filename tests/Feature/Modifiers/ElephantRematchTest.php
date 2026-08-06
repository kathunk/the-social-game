<?php

use App\Challenges\ElephantInTheRoom\ElephantMatch;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use App\Models\Game;
use App\Modifiers\ElephantInTheRoom\ElephantRematch;
use App\States\ChallengeState;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function rematchMutateState(Challenge $challenge, callable $mutator): Challenge
{
    $state = ChallengeState::load($challenge->id);
    $mutator($state);
    Challenge::find($challenge->id)?->update(['challenge_data' => $state->challenge_data]);

    return $challenge->fresh();
}

/**
 * Boots a started elephant game whose template includes the rematch modifier.
 * Returns players sorted by id — index 0 is the creator, who moves first.
 */
function setupRematchGame($test, bool $bot_game = false): array
{
    Verbs::commitImmediately();

    $test->mockGameTemplate(
        challenges: [['challenge_keys' => [ElephantMatch::key()], 'duration' => 20]],
        type: 'individual',
        modifiers: [ElephantRematch::key()],
        min_players: $bot_game ? 1 : 2,
        max_players: $bot_game ? 1 : 2,
        scoreboard_type: 'none',
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

function rematchCallAction($test, $player, string $action, string $type, string $class_key, array $props = [])
{
    $test->actingAs($player->user);

    $component = Livewire::test(GameDashboard::class, ['game' => $test->game->fresh()]);

    foreach ($props as $key => $value) {
        $component->set("round_properties.{$class_key}.{$key}", $value);
    }

    return $component->call('callClassAction', $action, $type, $class_key);
}

/**
 * Plays the match to a win for players[0]: square shape, three tiles down,
 * the winning slide into the empty corner. ProgressChallenge runs
 * synchronously in tests, so the challenge AND the game end here.
 */
function finishMatchWithWinner($test, $players, Challenge $challenge): void
{
    $winner = (string) $players[0]->id;

    rematchMutateState($challenge, function ($state) use ($winner) {
        $state->challenge_data['victory_shape'] = 'square';
        $state->challenge_data['board'][2] = $winner;
        $state->challenge_data['board'][5] = $winner;
        $state->challenge_data['board'][6] = $winner;
        $state->challenge_data['hands'][$winner] = 5;
        $state->challenge_data['elephant_space'] = 16;
    });

    rematchCallAction($test, $players[0], 'slideTile', 'challenge', ElephantMatch::key(), [
        'entry_space' => 1, 'direction' => 'right', 'client_move_id' => 'winning-move',
    ])->assertHasNoErrors();

    expect($test->game->fresh()->status)->toBe('ended');
}

// ─────────────────────────────────────────────────────────────────────────
// The post-game surface
// ─────────────────────────────────────────────────────────────────────────

it('renders no rematch UI while the game is active', function () {
    ['players' => $players] = setupRematchGame($this);

    $this->actingAs($players[0]->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertDontSee('Rematch');
});

it('shows the rematch card with the match result once the game ends', function () {
    ['players' => $players, 'challenge' => $challenge] = setupRematchGame($this);

    finishMatchWithWinner($this, $players, $challenge);

    $this->actingAs($players[0]->user);
    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('You won!')
        ->assertSee('Rematch');

    $this->actingAs($players[1]->user);
    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee($players[0]->name.' won.')
        ->assertSee('Rematch');
});

it('renders no scoreboard for a mode with scoreboard_type none', function () {
    ['players' => $players, 'challenge' => $challenge] = setupRematchGame($this);

    finishMatchWithWinner($this, $players, $challenge);

    $this->actingAs($players[0]->user);
    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertDontSee('Scoreboard');
});

// ─────────────────────────────────────────────────────────────────────────
// Voting
// ─────────────────────────────────────────────────────────────────────────

it('records the first vote without creating a game', function () {
    ['players' => $players, 'challenge' => $challenge] = setupRematchGame($this);

    finishMatchWithWinner($this, $players, $challenge);

    rematchCallAction($this, $players[1], 'requestRematch', 'modifier', ElephantRematch::key())
        ->assertHasNoErrors();

    $modifier = $this->game->fresh()->modifiers->firstWhere('class_key', ElephantRematch::key());

    expect($modifier->modifier_data['rematch_votes'])->toBe([$players[1]->id]);
    expect($modifier->modifier_data['rematch_game_id'])->toBeNull();
    expect(Game::count())->toBe(1);
});

it('rejects a duplicate vote', function () {
    ['players' => $players, 'challenge' => $challenge] = setupRematchGame($this);

    finishMatchWithWinner($this, $players, $challenge);

    rematchCallAction($this, $players[1], 'requestRematch', 'modifier', ElephantRematch::key())
        ->assertHasNoErrors();

    rematchCallAction($this, $players[1], 'requestRematch', 'modifier', ElephantRematch::key())
        ->assertHasErrors('action_error');

    expect(Game::count())->toBe(1);
});

it('ignores a rematch request while the game is still active', function () {
    ['players' => $players] = setupRematchGame($this);

    // While the game is active the modifier's component is empty, so
    // callClassAction returns before ever invoking the action — the surface
    // itself gates post-game actions (the action's own status guard is a
    // second line of defense)
    rematchCallAction($this, $players[1], 'requestRematch', 'modifier', ElephantRematch::key())
        ->assertHasNoErrors();

    $modifier = $this->game->fresh()->modifiers->firstWhere('class_key', ElephantRematch::key());

    expect($modifier->modifier_data['rematch_votes'])->toBe([]);
    expect(Game::count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────
// The rematch itself
// ─────────────────────────────────────────────────────────────────────────

it('creates and starts the rematch when both players opt in, with the loser going first', function () {
    ['players' => $players, 'challenge' => $challenge] = setupRematchGame($this);

    finishMatchWithWinner($this, $players, $challenge); // players[0] wins, players[1] loses

    rematchCallAction($this, $players[1], 'requestRematch', 'modifier', ElephantRematch::key())
        ->assertHasNoErrors();

    $rematch_component = rematchCallAction($this, $players[0], 'requestRematch', 'modifier', ElephantRematch::key());

    $rematch = Game::where('id', '!=', $this->game->id)->first();

    expect($rematch)->not->toBeNull();
    $rematch_component->assertRedirect(route('game-dashboard', ['game' => $rematch]));

    expect($rematch->status)->toBe('active');
    expect($rematch->players)->toHaveCount(2);
    expect($rematch->players->pluck('user_id')->sort()->values()->all())
        ->toBe($this->game->players->pluck('user_id')->sort()->values()->all());

    // Loser goes first: the loser created the rematch, and the creator (lowest
    // player id) is the first actor
    $loser_player_in_rematch = $rematch->players->firstWhere('user_id', $players[1]->user_id);
    $rematch_match = $rematch->challenges->firstWhere('class_key', ElephantMatch::key());

    expect($rematch_match->challenge_data['actor_order'][0])
        ->toBe((string) $loser_player_in_rematch->id);

    // The original game's modifier remembers where everyone went
    $modifier = $this->game->fresh()->modifiers->firstWhere('class_key', ElephantRematch::key());
    expect((int) $modifier->modifier_data['rematch_game_id'])->toBe($rematch->id);
});

it('redirects a re-tap to the existing rematch instead of creating another', function () {
    ['players' => $players, 'challenge' => $challenge] = setupRematchGame($this);

    finishMatchWithWinner($this, $players, $challenge);

    rematchCallAction($this, $players[1], 'requestRematch', 'modifier', ElephantRematch::key())
        ->assertHasNoErrors();
    rematchCallAction($this, $players[0], 'requestRematch', 'modifier', ElephantRematch::key());

    $rematch = Game::where('id', '!=', $this->game->id)->first();

    rematchCallAction($this, $players[0], 'requestRematch', 'modifier', ElephantRematch::key())
        ->assertRedirect(route('game-dashboard', ['game' => $rematch]));

    expect(Game::count())->toBe(2);
});

it('rematches a bot game on a single tap', function () {
    ['players' => $players, 'challenge' => $challenge] = setupRematchGame($this, bot_game: true);

    finishMatchWithWinner($this, $players, $challenge);

    $component = rematchCallAction($this, $players[0], 'requestRematch', 'modifier', ElephantRematch::key());

    $rematch = Game::where('id', '!=', $this->game->id)->first();

    expect($rematch)->not->toBeNull();
    $component->assertRedirect(route('game-dashboard', ['game' => $rematch]));

    expect($rematch->status)->toBe('active');
    expect($rematch->players)->toHaveCount(1);

    $rematch_match = $rematch->challenges->firstWhere('class_key', ElephantMatch::key());
    expect($rematch_match->challenge_data['is_bot_game'])->toBeTrue();
});
