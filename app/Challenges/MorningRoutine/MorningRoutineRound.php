<?php

namespace App\Challenges\MorningRoutine;

use App\Challenges\BaseChallengeClass;
use App\Challenges\MorningRoutine\Rewards\RewardRegistry;
use App\Events\GameUpdatedForReverb;
use App\Events\MorningRoutine\PlayerBusted;
use App\Events\MorningRoutine\PlayerCleanedRoom;
use App\Events\MorningRoutine\PlayerEnteredRoom;
use App\Events\MorningRoutine\PlayerExitedRoom;
use App\Events\MorningRoutine\PlayerLeftHouse;
use App\Events\MorningRoutine\PlayerLeftQueue;
use App\Events\MorningRoutine\PlayerQueuedForRoom;
use App\Events\MorningRoutine\PlayerStartedCleaning;
use App\Events\MorningRoutine\PlayerTookReward;
use App\Models\Player;
use App\States\GameState;
use Illuminate\Support\Facades\Cache;
use Thunk\Verbs\Facades\Verbs;

class MorningRoutineRound extends BaseChallengeClass
{
    const NAME = 'Morning Routine';

    const DESCRIPTION = 'Navigate the hallway and rooms before time runs out. Take rewards, but watch out for the mess.';

    const TYPE = 'individual';

    const HIDE_SCOREBOARD = true;

    const ROOMS = ['bathroom', 'kitchen', 'laundry', 'study'];

    const SECONDS_PER_MESS_CLEAN = 15;

    const STRANDED_PENALTY = 1;

    public static function key(): string
    {
        return 'morning_routine_round';
    }

    public function dataArrayForState(): array
    {
        $players = $this->challenge->game->players;
        $reward_count_per_room = $players->count() + 2;

        $player_locations = $players->mapWithKeys(fn ($player) => [
            $player->id => 'hallway',
        ])->toArray();

        $available_rewards = [];
        $room_mess = [];
        $room_queues = [];
        foreach (self::ROOMS as $room) {
            $available_rewards[$room] = RewardRegistry::randomKeysForRoom($room, $reward_count_per_room);
            $room_mess[$room] = 0;
            $room_queues[$room] = [];
        }

        return [
            'player_locations' => $player_locations,
            'room_mess' => $room_mess,
            'available_rewards' => $available_rewards,
            'taken_rewards' => array_fill_keys(self::ROOMS, []),
            'player_taken_in_rooms' => [],
            'room_queues' => $room_queues,
            'cleaning_state' => [],
            'player_points' => $players->mapWithKeys(fn ($p) => [$p->id => 0])->toArray(),
            'player_penalties' => $players->mapWithKeys(fn ($p) => [$p->id => 0])->toArray(),
            'point_log' => [],
            'exit_order' => [],
            'active_effects' => [],
            'toasts' => [],
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $data = $this->challenge->challenge_data;
        $current_location = $data['player_locations'][$player->id] ?? 'hallway';

        return $this->form()
            ->morningRoutine(
                player: $player,
                challenge_data: $data,
                rooms: self::ROOMS,
                players: $this->challenge->game->players,
                seconds_per_mess_clean: self::SECONDS_PER_MESS_CLEAN,
                ends_at_ts: $this->challenge->ends_at?->timestamp,
            )
            ->poll(2000)
            ->build();
    }

    /**
     * Lifecycle hook: dispatched by ChallengeEnded (handler is built fromState,
     * so read challenge_data off challenge_state — the model is null here).
     *
     * Finalizes the round: penalizes players stranded in a room, resolves
     * end-of-game reward effects, then writes every point_log entry to the
     * owning player's score history. The finalized point_log is written back
     * to challenge_state so the results round can display the full ledger.
     */
    public function onChallengeEnded(GameState $game_state)
    {
        $data = $this->challenge_state->challenge_data;

        // Players still in a room when time ran out failed to get out the door
        foreach ($data['player_locations'] ?? [] as $player_id => $location) {
            if ($location === 'hallway' || $location === 'left') {
                continue;
            }

            $data['player_penalties'][$player_id] =
                ($data['player_penalties'][$player_id] ?? 0) + self::STRANDED_PENALTY;

            $data['point_log'][] = [
                'player_id' => (int) $player_id,
                'points' => -self::STRANDED_PENALTY,
                'type' => 'stranded',
                'label' => "Still in the {$location} when time ran out",
            ];
        }

        // End-of-game reward effect bonuses (Mirror, Gambler's Fallacy, etc.)
        foreach ($data['taken_rewards'] ?? [] as $room => $rewards_in_room) {
            foreach ($rewards_in_room as $reward_key => $taker_id) {
                $reward = RewardRegistry::find($reward_key);
                if (! $reward || ! $reward->hasEffect()) {
                    continue;
                }

                $effect_class = $reward->effect_class;
                $effect = new $effect_class;
                $bonus = $effect->onChallengeEnded((int) $taker_id, $data);

                if ($bonus === 0) {
                    continue;
                }

                $data['player_points'][$taker_id] =
                    ($data['player_points'][$taker_id] ?? 0) + $bonus;

                $data['point_log'][] = [
                    'player_id' => (int) $taker_id,
                    'points' => $bonus,
                    'type' => 'effect',
                    'label' => "{$reward->name} (end of game)",
                ];
            }
        }

        $this->challenge_state->challenge_data = $data;

        // Itemized score history: one entry per point_log line
        $logs = collect($data['point_log'] ?? []);
        $icons = [
            'reward' => '🌅',
            'effect' => '✨',
            'bust' => '🚨',
            'exit' => '🏃',
            'stranded' => '😴',
        ];

        $game_state->players()->each(function ($player) use ($logs, $icons, $data) {
            $entries = $logs->where('player_id', $player->id);

            foreach ($entries as $entry) {
                if (($entry['points'] ?? 0) === 0) {
                    continue;
                }

                $player->addToScoreHistory(
                    icon: $icons[$entry['type']] ?? '🌅',
                    points: $entry['points'],
                    description: 'Morning Routine — '.$entry['label'],
                );
            }

            // Safety net: if anything changed the aggregates without logging
            // itemized entries, reconcile so the scoreboard total stays true.
            $expected = ($data['player_points'][$player->id] ?? 0)
                - ($data['player_penalties'][$player->id] ?? 0);
            $remainder = $expected - $entries->sum('points');

            if ($remainder !== 0) {
                $player->addToScoreHistory(
                    icon: '🌅',
                    points: $remainder,
                    description: 'Morning Routine — other adjustments',
                );
            }
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Actions
    // ─────────────────────────────────────────────────────────────────────

    public function enterRoom(Player $player, array $params): void
    {
        $this->withChallengeLock(function () use ($player, $params) {
            $room = $params['room'] ?? null;

            $this->assertNotCleaning($player);

            PlayerEnteredRoom::fire(
                game_id: $player->game_id,
                challenge_id: $this->challenge->id,
                player_id: $player->id,
                room: $room,
            );

            Verbs::commit();
            event(new GameUpdatedForReverb($player->game->fresh()));
        });
    }

    public function exitRoom(Player $player, array $params): void
    {
        $this->withChallengeLock(function () use ($player, $params) {
            $this->doExitRoom($player, $params);
        });
    }

    protected function doExitRoom(Player $player, array $params): void
    {
        $data = $this->challenge->challenge_data;
        $current = $data['player_locations'][$player->id] ?? 'hallway';

        if ($current === 'hallway') {
            throw new \RuntimeException('You are already in the hallway.');
        }

        if ($current === 'left') {
            throw new \RuntimeException('You already left the house.');
        }

        $room = $current;

        // Auto-finalize any in-progress cleaning
        if (isset($data['cleaning_state'][$player->id])) {
            PlayerCleanedRoom::fire(
                game_id: $player->game_id,
                challenge_id: $this->challenge->id,
                player_id: $player->id,
                room: $room,
                finished_at: now()->timestamp,
            );
            Verbs::commit();
            $data = $this->challenge->fresh()->challenge_data;
        }

        $room_mess = $data['room_mess'][$room] ?? 0;
        $queued_player_id = $data['room_queues'][$room][0] ?? null;

        $is_bust = $room_mess > 0 && $queued_player_id !== null;

        PlayerExitedRoom::fire(
            game_id: $player->game_id,
            challenge_id: $this->challenge->id,
            player_id: $player->id,
        );

        if ($is_bust) {
            PlayerBusted::fire(
                game_id: $player->game_id,
                challenge_id: $this->challenge->id,
                busted_player_id: $player->id,
                buster_player_id: $queued_player_id,
                room: $room,
                mess_amount: $room_mess,
            );
        }

        // Auto-enter the queued player (if any)
        if ($queued_player_id !== null) {
            PlayerEnteredRoom::fire(
                game_id: $player->game_id,
                challenge_id: $this->challenge->id,
                player_id: $queued_player_id,
                room: $room,
                from_queue: true,
            );
        }

        Verbs::commit();
        event(new GameUpdatedForReverb($player->game->fresh()));
    }

    public function takeReward(Player $player, array $params): void
    {
        $this->withChallengeLock(function () use ($player, $params) {
            $reward_key = $params['reward_key'] ?? null;
            $room = $this->challenge->challenge_data['player_locations'][$player->id] ?? null;

            if ($room === null || $room === 'hallway') {
                throw new \RuntimeException('You must be in a room to take a reward.');
            }

            PlayerTookReward::fire(
                game_id: $player->game_id,
                challenge_id: $this->challenge->id,
                player_id: $player->id,
                room: $room,
                reward_key: $reward_key,
            );

            Verbs::commit();
            event(new GameUpdatedForReverb($player->game->fresh()));
        });
    }

    public function startCleaning(Player $player, array $params): void
    {
        $this->withChallengeLock(function () use ($player) {
            $room = $this->challenge->challenge_data['player_locations'][$player->id] ?? null;

            if ($room === null || $room === 'hallway') {
                throw new \RuntimeException('You must be in a room to clean it.');
            }

            PlayerStartedCleaning::fire(
                game_id: $player->game_id,
                challenge_id: $this->challenge->id,
                player_id: $player->id,
                room: $room,
                started_at: now()->timestamp,
            );

            Verbs::commit();
            event(new GameUpdatedForReverb($player->game->fresh()));
        });
    }

    public function stopCleaning(Player $player, array $params): void
    {
        $this->withChallengeLock(function () use ($player) {
            $cleaning = $this->challenge->challenge_data['cleaning_state'][$player->id] ?? null;

            if ($cleaning === null) {
                throw new \RuntimeException('You are not cleaning.');
            }

            PlayerCleanedRoom::fire(
                game_id: $player->game_id,
                challenge_id: $this->challenge->id,
                player_id: $player->id,
                room: $cleaning['room'],
                finished_at: now()->timestamp,
            );

            Verbs::commit();
            event(new GameUpdatedForReverb($player->game->fresh()));
        });
    }

    public function leaveHouse(Player $player, array $params): void
    {
        $this->withChallengeLock(function () use ($player) {
            $this->assertNotCleaning($player);

            PlayerLeftHouse::fire(
                game_id: $player->game_id,
                challenge_id: $this->challenge->id,
                player_id: $player->id,
            );

            Verbs::commit();
            event(new GameUpdatedForReverb($player->game->fresh()));
        });
    }

    public function queueForRoom(Player $player, array $params): void
    {
        $this->withChallengeLock(function () use ($player, $params) {
            $room = $params['room'] ?? null;

            $this->assertNotCleaning($player);

            PlayerQueuedForRoom::fire(
                game_id: $player->game_id,
                challenge_id: $this->challenge->id,
                player_id: $player->id,
                room: $room,
            );

            Verbs::commit();
            event(new GameUpdatedForReverb($player->game->fresh()));
        });
    }

    public function leaveQueue(Player $player, array $params): void
    {
        $this->withChallengeLock(function () use ($player, $params) {
            $room = $params['room'] ?? null;

            PlayerLeftQueue::fire(
                game_id: $player->game_id,
                challenge_id: $this->challenge->id,
                player_id: $player->id,
                room: $room,
            );

            Verbs::commit();
            event(new GameUpdatedForReverb($player->game->fresh()));
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    protected function assertNotCleaning(Player $player): void
    {
        $cleaning = $this->challenge->challenge_data['cleaning_state'][$player->id] ?? null;
        if ($cleaning !== null) {
            throw new \RuntimeException('Stop cleaning before moving.');
        }
    }

    /**
     * Serialize action handlers for a single challenge to prevent race
     * conditions (e.g. two players entering the same room at the same time).
     *
     * Acquires a per-challenge cache lock and refreshes the challenge model
     * inside the lock so the action sees the latest state.
     */
    protected function withChallengeLock(callable $callback)
    {
        $lock = Cache::lock("morning-routine:challenge:{$this->challenge->id}", 5);

        if (! $lock->block(3)) {
            throw new \RuntimeException('The room is busy. Try again in a moment.');
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
