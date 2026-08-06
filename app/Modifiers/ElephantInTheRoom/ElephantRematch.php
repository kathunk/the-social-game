<?php

namespace App\Modifiers\ElephantInTheRoom;

use App\Challenges\ElephantInTheRoom\ElephantMatch;
use App\Events\ElephantInTheRoom\PlayerWantsRematch;
use App\Events\ElephantInTheRoom\RematchGameCreated;
use App\Events\GameUpdatedForReverb;
use App\Models\Game;
use App\Models\Player;
use App\Models\User;
use App\Modifiers\BaseModifierClass;
use Illuminate\Support\Facades\Cache;
use Thunk\Verbs\Facades\Verbs;

/**
 * Post-game rematch for Elephant in the Room. Shows nothing during the game;
 * after it ends, the postGameComponent surface renders the match result and a
 * rematch card. Bot games rematch on one tap; 2-player games need both
 * players to opt in. The rematch is a brand-new game from the same template,
 * created with the LOSER as creator — the creator plays first, so the loser
 * gets the first move (the original game's rematch rule).
 */
class ElephantRematch extends BaseModifierClass
{
    const NAME = 'Elephant Rematch';

    const DESCRIPTION = 'Offer a rematch when the match is over.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'elephant_rematch';
    }

    public function dataArrayForState(?Game $game = null): array
    {
        return [
            'rematch_votes' => [],
            'rematch_game_id' => null,
        ];
    }

    public function postGameComponent(Player $player): array
    {
        return $this->form()
            ->elephantRematch(
                player: $player,
                modifier_data: $this->modifier->modifier_data,
                match_data: $this->endedMatch()?->challenge_data ?? [],
                players: $this->modifier->game->players,
            )
            ->poll(3000)
            ->build();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Actions
    // ─────────────────────────────────────────────────────────────────────

    public function requestRematch(Player $player, array $params)
    {
        return $this->withModifierLock(function () use ($player) {
            $game = $player->game;

            if ($game->status !== 'ended') {
                throw new \RuntimeException('The game is not over yet.');
            }

            $data = $this->modifier->modifier_data;

            // Idempotent re-tap: the rematch already exists, just go there
            if (! empty($data['rematch_game_id'])) {
                return redirect()->route('game-dashboard', ['game' => $data['rematch_game_id']]);
            }

            PlayerWantsRematch::fire(
                game_id: $game->id,
                modifier_id: $this->modifier->id,
                player_id: $player->id,
            );

            Verbs::commit();

            $data = $this->modifier->fresh()->modifier_data;

            // 2-player games need both opt-ins; bot games have one player
            if (count($data['rematch_votes'] ?? []) < $game->players->count()) {
                event(new GameUpdatedForReverb($game->fresh()));

                return null;
            }

            $rematch = $this->createRematchGame($game);

            RematchGameCreated::fire(
                game_id: $game->id,
                modifier_id: $this->modifier->id,
                rematch_game_id: $rematch->id,
            );

            Verbs::commit();

            // The opponent's ended-game dashboard reloads on this broadcast
            // (or on its next poll) and auto-forwards to the rematch
            event(new GameUpdatedForReverb($game->fresh()));

            return redirect()->route('game-dashboard', ['game' => $rematch]);
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    protected function createRematchGame(Game $game): Game
    {
        $creator = $this->rematchCreator($game);

        $rematch = Game::fromTemplate(
            template: $game->gameTemplate,
            game_mode: $game->gameMode,
            user: $creator,
            requires_admin_approval_to_join: false,
            starts_at: now(),
            is_public: false,
        );

        $game->players
            ->filter(fn ($p) => $p->user_id !== $creator->id)
            ->each(fn ($p) => $p->user->requestToJoinGame($rematch));

        $rematch = $rematch->fresh()->start();

        Verbs::commit();

        return $rematch;
    }

    /**
     * The loser goes first in the rematch, and the game creator plays first —
     * so the loser creates the game. Draws and no-result endings alternate:
     * whoever went second last time creates.
     */
    protected function rematchCreator(Game $game): User
    {
        $match_data = $this->endedMatch()?->challenge_data ?? [];
        $victors = $match_data['victor_ids'] ?? [];
        $real_actors = array_values(array_filter(
            $match_data['actor_order'] ?? [],
            fn ($actor) => $actor !== ElephantMatch::BOT_ID,
        ));

        $creator_actor_id = null;

        if (count($real_actors) === 1) {
            $creator_actor_id = $real_actors[0];
        } else {
            $losers = array_values(array_filter(
                $real_actors,
                fn ($actor) => ! in_array($actor, $victors, true),
            ));

            $creator_actor_id = count($losers) === 1
                ? $losers[0]
                : ($real_actors[1] ?? null);
        }

        $creator = $creator_actor_id ? Player::find((int) $creator_actor_id)?->user : null;

        return $creator ?? $game->players->sortBy('id')->last()->user;
    }

    protected function endedMatch()
    {
        return $this->modifier->game->challenges
            ->where('class_key', ElephantMatch::key())
            ->where('status', 'ended')
            ->sortByDesc('round_number')
            ->first();
    }

    /**
     * Serialize rematch actions so two simultaneous opt-ins can't create two
     * games. Mirrors ElephantMatch::withChallengeLock.
     */
    protected function withModifierLock(callable $callback)
    {
        $lock = Cache::lock("elephant-rematch:modifier:{$this->modifier->id}", 10);

        if (! $lock->block(5)) {
            throw new \RuntimeException('Hang on — try again in a moment.');
        }

        try {
            $this->modifier = $this->modifier->fresh();

            return $callback();
        } finally {
            $lock->release();
        }
    }
}
