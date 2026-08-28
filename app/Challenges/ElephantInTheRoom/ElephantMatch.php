<?php

namespace App\Challenges\ElephantInTheRoom;

use App\Challenges\BaseChallengeClass;
use App\Challenges\ElephantInTheRoom\Support\BoardLogic;
use App\Events\ElephantInTheRoom\BotDifficultySet;
use App\Events\ElephantInTheRoom\ElephantMoved;
use App\Events\ElephantInTheRoom\MatchForfeited;
use App\Events\ElephantInTheRoom\TileSlid;
use App\Events\GameUpdatedForReverb;
use App\Jobs\ProgressChallenge;
use App\Models\Player;
use App\States\GameState;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Thunk\Verbs\Facades\Verbs;

class ElephantMatch extends BaseChallengeClass
{
    const NAME = 'Elephant in the Room';

    const DESCRIPTION = 'Slide tiles onto a 4x4 board and be the first to form your victory shape. The elephant blocks the way — move it wisely.';

    const TYPE = 'individual';

    const HIDE_SCOREBOARD = true;

    // The virtual opponent in single-player games. Not a Player row — just a
    // sentinel actor id inside challenge_data. Its brain runs client-side;
    // the server only validates that its moves are legal.
    const BOT_ID = 'bot';

    // The bot's brain lives client-side (see the elephant-board blade); this
    // list is the server-authoritative set of difficulties it can be asked
    // to play. "normal" is the original greedy bot; "hard" adds joint
    // slide+elephant scoring; "impossible" adds opponent best-reply search.
    const BOT_DIFFICULTIES = ['normal', 'hard', 'impossible'];

    const TURN_SECONDS = 35;

    const FORFEIT_GRACE_SECONDS = 3;

    const WIN_POINTS = 1;

    // How long clients get to show the final move + victory state before the
    // challenge formally ends and the dashboard transitions to post-game
    const END_TRANSITION_SECONDS = 4;

    public static function key(): string
    {
        return 'elephant_match';
    }

    public function dataArrayForState(): array
    {
        // Player ids are snowflakes (time-ordered), so sorting by id puts the
        // game creator first: the creator plays first and is Orange
        $players = $this->challenge->game->players->sortBy('id')->values();
        $is_bot_game = $players->count() === 1;

        $actor_order = $players->map(fn ($player) => (string) $player->id)->all();

        if ($is_bot_game) {
            $actor_order[] = self::BOT_ID;
        }

        return [
            'board' => BoardLogic::emptyBoard(),
            'elephant_space' => 6,
            'phase' => 'tile',
            'current_actor_id' => $actor_order[0],
            'actor_order' => $actor_order,
            'hands' => array_fill_keys($actor_order, 8),
            'victory_shape' => Arr::random($is_bot_game ? BoardLogic::BOT_SHAPES : BoardLogic::SHAPES),
            'is_bot_game' => $is_bot_game,
            // Chosen by the human on the board before the first move of a
            // bot game; stays null (and unused) in 2-player games
            'bot_difficulty' => null,
            'match_status' => 'active',
            'victor_ids' => [],
            'winning_spaces' => [],
            'turn_started_at' => now()->timestamp,
            'moves' => [],
            'last_seq' => 0,
        ];
    }

    public function frontendComponent(Player $player): array
    {
        return $this->form()
            ->elephantBoard(
                player: $player,
                challenge_data: $this->challenge->challenge_data,
                players: $this->challenge->game->players,
                game_id: $this->challenge->game_id,
            )
            ->poll(3000)
            ->build();
    }

    /**
     * Lifecycle hook: dispatched by ChallengeEnded (handler is built fromState,
     * so read challenge_data off challenge_state — the model is null here).
     *
     * Victors get their point. A dead draw (double-empty hands or the match
     * clock running out) records both players as victors only when both
     * actually completed their shape; otherwise nobody scores.
     */
    public function onChallengeEnded(GameState $game_state)
    {
        $data = $this->challenge_state->challenge_data;
        $victors = $data['victor_ids'] ?? [];

        if ($victors === []) {
            return;
        }

        $game_state->players()->each(function ($player) use ($victors) {
            if (in_array((string) $player->id, $victors, true)) {
                $player->addToScoreHistory(
                    icon: '🐘',
                    points: self::WIN_POINTS,
                    description: 'Elephant in the Room — won the match',
                );
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Actions
    // ─────────────────────────────────────────────────────────────────────

    public function setBotDifficulty(Player $player, array $params): void
    {
        $this->withChallengeLock(function () use ($player, $params) {
            BotDifficultySet::fire(
                game_id: $player->game_id,
                challenge_id: $this->challenge->id,
                actor_id: (string) $player->id,
                difficulty: (string) ($params['difficulty'] ?? ''),
                set_at: now()->timestamp,
            );

            Verbs::commit();
            $this->afterMoves($player);
        });
    }

    public function slideTile(Player $player, array $params): void
    {
        $this->withChallengeLock(function () use ($player, $params) {
            TileSlid::fire(
                game_id: $player->game_id,
                challenge_id: $this->challenge->id,
                actor_id: (string) $player->id,
                entry_space: (int) ($params['entry_space'] ?? 0),
                direction: (string) ($params['direction'] ?? ''),
                client_move_id: (string) ($params['client_move_id'] ?? ''),
            );

            Verbs::commit();
            $this->afterMoves($player);
        });
    }

    public function moveElephant(Player $player, array $params): void
    {
        $this->withChallengeLock(function () use ($player, $params) {
            ElephantMoved::fire(
                game_id: $player->game_id,
                challenge_id: $this->challenge->id,
                actor_id: (string) $player->id,
                to_space: (int) ($params['to_space'] ?? 0),
                client_move_id: (string) ($params['client_move_id'] ?? ''),
                moved_at: now()->timestamp,
            );

            Verbs::commit();
            $this->afterMoves($player);
        });
    }

    /**
     * Records the bot's full turn in a single-player game. The bot's brain
     * runs client-side (see the elephant-board blade); the events below
     * validate that the reported moves are legal, so a buggy client can't
     * put the board in an impossible state — it just can't pick for you.
     */
    public function playBotTurn(Player $player, array $params): void
    {
        $this->withChallengeLock(function () use ($player, $params) {
            $data = $this->challenge->challenge_data;

            if (! ($data['is_bot_game'] ?? false)) {
                throw new \RuntimeException('This is not a bot game.');
            }

            TileSlid::fire(
                game_id: $player->game_id,
                challenge_id: $this->challenge->id,
                actor_id: self::BOT_ID,
                entry_space: (int) ($params['bot_entry_space'] ?? 0),
                direction: (string) ($params['bot_direction'] ?? ''),
                client_move_id: (string) ($params['bot_tile_move_id'] ?? ''),
            );

            Verbs::commit();

            // The slide may have ended the match (bot win, or a push that
            // completed the human's shape) — only move the elephant if not
            if (($this->challenge->fresh()->challenge_data['match_status'] ?? null) === 'active') {
                ElephantMoved::fire(
                    game_id: $player->game_id,
                    challenge_id: $this->challenge->id,
                    actor_id: self::BOT_ID,
                    to_space: (int) ($params['bot_to_space'] ?? 0),
                    client_move_id: (string) ($params['bot_elephant_move_id'] ?? ''),
                    moved_at: now()->timestamp,
                );

                Verbs::commit();
            }

            $this->afterMoves($player);
        });
    }

    public function claimForfeit(Player $player, array $params): void
    {
        $this->withChallengeLock(function () use ($player) {
            MatchForfeited::fire(
                game_id: $player->game_id,
                challenge_id: $this->challenge->id,
                claimant_id: (string) $player->id,
                forfeited_at: now()->timestamp,
            );

            Verbs::commit();
            $this->afterMoves($player);
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Post-commit bookkeeping shared by every action.
     *
     * In 2-player games, GameUpdatedForReverb is fired at turn boundaries
     * (after the elephant move, or when the match completes) — not after the
     * mid-turn tile slide. The dashboard answers it with its usual full
     * refresh, and the board blade replays whatever the reloading client
     * hasn't animated yet from its localStorage snapshot. Bot games are
     * single-client, so nothing is broadcast mid-match.
     *
     * When the match completes, the formal challenge end is scheduled after a
     * short delay so clients can show the final move and victory state before
     * the dashboard transitions to post-game.
     */
    protected function afterMoves(Player $player): void
    {
        $this->challenge = $this->challenge->fresh();
        $data = $this->challenge->challenge_data;

        $match_complete = ($data['match_status'] ?? null) === 'complete';
        $turn_boundary = ($data['phase'] ?? null) === 'tile';

        if (! ($data['is_bot_game'] ?? false) && ($match_complete || $turn_boundary)) {
            event(new GameUpdatedForReverb($player->game->fresh()));
        }

        if ($match_complete) {
            ProgressChallenge::dispatch($this->challenge)
                ->delay(now()->addSeconds(self::END_TRANSITION_SECONDS));
        }
    }

    public function stateSnapshot(array $data): array
    {
        return array_merge(Arr::only($data, [
            'board',
            'elephant_space',
            'phase',
            'current_actor_id',
            'actor_order',
            'hands',
            'victory_shape',
            'is_bot_game',
            'bot_difficulty',
            'match_status',
            'victor_ids',
            'winning_spaces',
            'turn_started_at',
            'last_seq',
            'repetition_loss_by',
        ]), [
            // Trailing identical-slide run per actor, so the client can warn
            // at three in a row and bots can refuse a fatal fourth
            'slide_runs' => BoardLogic::trailingSlideRuns($data['moves'] ?? []),
        ]);
    }

    /**
     * Serialize action handlers for a single match to prevent race conditions
     * (e.g. a move and a forfeit claim landing at the same moment).
     */
    protected function withChallengeLock(callable $callback)
    {
        $lock = Cache::lock("elephant-match:challenge:{$this->challenge->id}", 5);

        if (! $lock->block(3)) {
            throw new \RuntimeException('The board is busy. Try again in a moment.');
        }

        try {
            // Refresh the challenge model so any state changes from the
            // previous lock holder are visible to this action.
            $this->challenge = $this->challenge->fresh();

            return $callback();
        } finally {
            $lock->release();
        }
    }
}
