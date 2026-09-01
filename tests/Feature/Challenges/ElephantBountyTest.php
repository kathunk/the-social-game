<?php

use App\Challenges\ElephantInTheRoom\ElephantMatch;
use App\Challenges\ElephantInTheRoom\Support\ImpossibleBotReward;
use App\Livewire\ElephantContestPage;
use App\Livewire\GameDashboard;
use App\Livewire\Home;
use App\Models\Challenge;
use App\Models\ElephantOfferCode;
use App\Modifiers\ElephantInTheRoom\ElephantRematch;
use App\Notifications\ElephantInTheRoom\ImpossibleBotDefeatedNotification;
use App\States\ChallengeState;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Boots a started single-player bot game (rematch modifier included, so the
 * post-game surface renders) on a mode named so the home-page promo can
 * find it. Returns players sorted by id and the match challenge.
 */
function bountyBotGame($test): array
{
    Verbs::commitImmediately();

    $test->mockGameTemplate(
        challenges: [['challenge_keys' => [ElephantMatch::key()], 'duration' => 20]],
        type: 'individual',
        modifiers: [ElephantRematch::key()],
        name: 'Elephant in the Room (vs Bot)',
        min_players: 1,
        max_players: 1,
        is_public: true,
        scoreboard_type: 'none',
    );

    $test->createGame();
    $test->game->start();

    return [
        'players' => $test->game->players->sortBy('id')->values(),
        'challenge' => Challenge::where('game_id', $test->game->id)->first(),
    ];
}

function bountyMutate(Challenge $challenge, callable $mutator): Challenge
{
    $state = ChallengeState::load($challenge->id);
    $mutator($state);
    Challenge::find($challenge->id)?->update(['challenge_data' => $state->challenge_data]);

    return $challenge->fresh();
}

function bountyAction($test, $player, $challenge, string $action, array $props = [])
{
    $test->actingAs($player->user);

    $component = Livewire::test(GameDashboard::class, ['game' => $player->game->fresh()]);

    foreach ($props as $key => $value) {
        $component->set("round_properties.{$challenge->class_key}.{$key}", $value);
    }

    return $component->call('callClassAction', $action, 'challenge', $challenge->class_key);
}

/**
 * Puts the human one slide from victory on impossible mode and plays the
 * winning slide, ending the match, challenge, and game.
 */
function bountyPlayWinningGame($test, $players, Challenge $challenge): void
{
    $human = (string) $players[0]->id;

    $challenge = bountyMutate($challenge, function ($state) use ($human) {
        $state->challenge_data['bot_difficulty'] = 'impossible';
        $state->challenge_data['victory_shape'] = 'square';
        $state->challenge_data['board'][2] = $human;
        $state->challenge_data['board'][5] = $human;
        $state->challenge_data['board'][6] = $human;
        $state->challenge_data['hands'][$human] = 5;
        $state->challenge_data['elephant_space'] = 16;
    });

    bountyAction($test, $players[0], $challenge, 'slideTile', [
        'entry_space' => 1, 'direction' => 'right', 'client_move_id' => 'winning-'.uniqid(),
    ])->assertHasNoErrors();

    expect($test->game->fresh()->status)->toBe('ended');
}

function bountySeedCodes(array $codes): void
{
    foreach ($codes as $code) {
        ElephantOfferCode::create(['code' => $code]);
    }
}

// ─────────────────────────────────────────────────────────────────────────
// The code pool
// ─────────────────────────────────────────────────────────────────────────

it('loads codes through the artisan command, skipping duplicates', function () {
    $this->artisan('elephant:add-offer-codes', ['codes' => ['CATA-1', 'CATA-2']])
        ->expectsOutputToContain('Added 2 code(s). Pool: 2 unclaimed')
        ->assertSuccessful();

    $this->artisan('elephant:add-offer-codes', ['codes' => ['CATA-2', 'CATA-3']])
        ->expectsOutputToContain('Added 1 code(s). Pool: 3 unclaimed')
        ->assertSuccessful();

    expect(ElephantOfferCode::count())->toBe(3);
});

// ─────────────────────────────────────────────────────────────────────────
// Winning the bounty
// ─────────────────────────────────────────────────────────────────────────

it('emails the winner their code and the owner a heads-up on an impossible win', function () {
    Notification::fake();
    bountySeedCodes(['CATA-FIRST', 'CATA-SECOND']);

    ['players' => $players, 'challenge' => $challenge] = bountyBotGame($this);
    bountyPlayWinningGame($this, $players, $challenge);

    $claim = ElephantOfferCode::where('code', 'CATA-FIRST')->first();
    expect((string) $claim->claimed_by_user_id)->toBe((string) $players[0]->user->id);
    expect($claim->source_challenge_id)->toBe($challenge->id);

    Notification::assertSentTo(
        $players[0]->user,
        ImpossibleBotDefeatedNotification::class,
        fn ($notification) => $notification->code === 'CATA-FIRST' && ! $notification->for_owner
    );

    Notification::assertSentOnDemand(
        ImpossibleBotDefeatedNotification::class,
        fn ($notification, $channels, $notifiable) => ($notifiable->routes['mail'] ?? null) === ImpossibleBotReward::OWNER_EMAIL
            && $notification->for_owner
            && $notification->code === 'CATA-FIRST'
            && $notification->codes_remaining === 1
            && $notification->winner_email === $players[0]->user->email
    );
});

it('shows the winner a celebration block on the post-game screen', function () {
    Notification::fake();
    bountySeedCodes(['CATA-FIRST']);

    ['players' => $players, 'challenge' => $challenge] = bountyBotGame($this);
    bountyPlayWinningGame($this, $players, $challenge);

    $this->actingAs($players[0]->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('You beat the impossible bot!')
        ->assertSee(ImpossibleBotReward::OFFER);
});

it('awards at most one code per user: a rematch win earns nothing extra', function () {
    Notification::fake();
    bountySeedCodes(['CATA-FIRST', 'CATA-SECOND']);

    ['players' => $players, 'challenge' => $challenge] = bountyBotGame($this);
    bountyPlayWinningGame($this, $players, $challenge);

    // The same player wins again via the one-tap bot rematch
    $this->actingAs($players[0]->user);
    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->call('callClassAction', 'requestRematch', 'modifier', ElephantRematch::key());

    $rematch = \App\Models\Game::where('id', '!=', $this->game->id)->firstOrFail();
    $this->game = $rematch;
    $rematch_players = $rematch->players->sortBy('id')->values();
    $rematch_challenge = Challenge::where('game_id', $rematch->id)->first();

    bountyPlayWinningGame($this, $rematch_players, $rematch_challenge);

    Notification::assertSentToTimes($players[0]->user, ImpossibleBotDefeatedNotification::class, 1);
    expect(ImpossibleBotReward::remainingCodes())->toBe(1);

    // The second game's post-game shows a plain result, no celebration
    Livewire::test(GameDashboard::class, ['game' => $rematch->fresh()])
        ->assertDontSee('You beat the impossible bot!');
});

it('sends an IOU to the winner and an SOS to the owner when the pool is empty', function () {
    Notification::fake();

    ['players' => $players, 'challenge' => $challenge] = bountyBotGame($this);
    bountyPlayWinningGame($this, $players, $challenge);

    Notification::assertSentTo(
        $players[0]->user,
        ImpossibleBotDefeatedNotification::class,
        fn ($notification) => $notification->code === null && ! $notification->for_owner
    );

    Notification::assertSentOnDemand(
        ImpossibleBotDefeatedNotification::class,
        fn ($notification) => $notification->for_owner
            && $notification->code === null
            && $notification->codes_remaining === 0
    );
});

it('does not reward a simultaneous draw, and tallies the record both ways', function () {
    Notification::fake();
    bountySeedCodes(['CATA-FIRST']);

    ['players' => $players, 'challenge' => $challenge] = bountyBotGame($this);
    $human = (string) $players[0]->id;

    // A completed match where both shapes finished at once
    bountyMutate($challenge, function ($state) use ($human) {
        $state->challenge_data['bot_difficulty'] = 'impossible';
        $state->challenge_data['match_status'] = 'complete';
        $state->challenge_data['victor_ids'] = [$human, ElephantMatch::BOT_ID];
    });
    $challenge->refresh()->end();
    Verbs::commit();

    Notification::assertNotSentTo($players[0]->user, ImpossibleBotDefeatedNotification::class);
    expect(ImpossibleBotReward::remainingCodes())->toBe(1);
    expect(ImpossibleBotReward::botRecord())->toBe(['bot' => 0, 'humans' => 0]);

    // Recast as an outright bot win: it lands in the bot's column
    bountyMutate($challenge, function ($state) {
        $state->challenge_data['victor_ids'] = [ElephantMatch::BOT_ID];
    });

    expect(ImpossibleBotReward::botRecord())->toBe(['bot' => 1, 'humans' => 0]);
});

// ─────────────────────────────────────────────────────────────────────────
// The promo surfaces
// ─────────────────────────────────────────────────────────────────────────

it('promotes the bounty on the home page with the offer, record, and a CTA into a bot game', function () {
    bountySeedCodes(['CATA-FIRST']);
    ['players' => $players] = bountyBotGame($this);

    $this->actingAs($players[0]->user);

    Livewire::test(Home::class)
        ->assertSee('Beat the bot on Impossible mode and earn a one-time code')
        ->assertSee(ImpossibleBotReward::OFFER)
        ->assertSee('Challenge the Bot');
});

it('hides the home promo when the pool is empty or the user already has a code', function () {
    ['players' => $players, 'challenge' => $challenge] = bountyBotGame($this);
    $this->actingAs($players[0]->user);

    // Empty pool: no promo
    Livewire::test(Home::class)->assertDontSee('Challenge the Bot');

    // Codes exist but this user already earned theirs: still no promo
    Notification::fake();
    bountySeedCodes(['CATA-FIRST', 'CATA-SECOND']);
    bountyPlayWinningGame($this, $players, $challenge);

    Livewire::test(Home::class)->assertDontSee('Challenge the Bot');
});

it('advertises the bounty on the impossible picker option, but not to players who claimed', function () {
    Notification::fake();
    bountySeedCodes(['CATA-FIRST', 'CATA-SECOND']);

    ['players' => $players, 'challenge' => $challenge] = bountyBotGame($this);
    $this->actingAs($players[0]->user);

    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->assertSee('Beat it and earn');

    bountyPlayWinningGame($this, $players, $challenge);

    // A fresh bot game via the rematch card: this player already has a
    // code, so the picker shows no bounty line
    Livewire::test(GameDashboard::class, ['game' => $this->game->fresh()])
        ->call('callClassAction', 'requestRematch', 'modifier', ElephantRematch::key());

    $rematch = \App\Models\Game::where('id', '!=', $this->game->id)->firstOrFail();

    Livewire::test(GameDashboard::class, ['game' => $rematch->fresh()])
        ->assertDontSee('Beat it and earn');
});

// ─────────────────────────────────────────────────────────────────────────
// The public /elephant page
// ─────────────────────────────────────────────────────────────────────────

it('serves the public page to guests with the offer and a signup CTA', function () {
    bountySeedCodes(['CATA-FIRST']);

    $this->get('/elephant')
        ->assertOk()
        ->assertSee('Sign up to play')
        ->assertSee('Log in')
        ->assertSee(ImpossibleBotReward::OFFER);
});

it('drops the offer from the public page when the pool is empty but keeps the record', function () {
    Livewire::test(ElephantContestPage::class)
        ->assertDontSee(ImpossibleBotReward::OFFER)
        ->assertSee('record on Impossible');
});
