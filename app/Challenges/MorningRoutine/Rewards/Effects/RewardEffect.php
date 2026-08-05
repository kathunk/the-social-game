<?php

namespace App\Challenges\MorningRoutine\Rewards\Effects;

use App\States\ChallengeState;

/**
 * Hook interface for reward effects in the MorningRoutine game mode.
 *
 * Effects are pure: they receive a mutable challenge_data array and the
 * id of the player who took the reward, and they return a (possibly mutated)
 * challenge_data array. The challenge class is responsible for persisting
 * the changes via Verbs events.
 *
 * Effects should not fire Verbs events directly - they describe state
 * transitions that the calling event handler applies.
 *
 * For "next time..." effects that need to fire later, set a flag in
 * $challenge_data['active_effects'][$player_id][$effect_key] = true.
 * The corresponding hook will check for this flag.
 */
abstract class RewardEffect
{
    /**
     * Called immediately when a player takes this reward.
     */
    public function onTaken(int $player_id, array $challenge_data): array
    {
        return $challenge_data;
    }

    /**
     * Called when any player exits a room (back to hallway).
     * The taker is the player who has this effect armed.
     */
    public function onPlayerExitedRoom(int $taker_id, int $exiting_player_id, string $room, array $challenge_data): array
    {
        return $challenge_data;
    }

    /**
     * Called when any player enters a room (from hallway).
     */
    public function onPlayerEnteredRoom(int $taker_id, int $entering_player_id, string $room, array $challenge_data): array
    {
        return $challenge_data;
    }

    /**
     * Called when any player is busted by another player.
     * Effects on the busted player can mitigate the penalty.
     * Effects on the buster can amplify it.
     */
    public function onPlayerBusted(int $taker_id, int $busted_player_id, int $buster_player_id, int &$penalty_amount, array $challenge_data): array
    {
        return $challenge_data;
    }

    /**
     * Called when a player queues for a room.
     */
    public function onPlayerQueuedForRoom(int $taker_id, int $queueing_player_id, string $room, array $challenge_data): array
    {
        return $challenge_data;
    }

    /**
     * Called when a player cleans mess from a room.
     */
    public function onRoomCleaned(int $taker_id, int $cleaning_player_id, string $room, int $mess_removed, array $challenge_data): array
    {
        return $challenge_data;
    }

    /**
     * Called at the end of the game.
     * Returns the bonus/penalty points to apply to the taker.
     */
    public function onChallengeEnded(int $taker_id, array $challenge_data): int
    {
        return 0;
    }

    /**
     * Helper: grant points to a player, keeping the aggregate total and the
     * itemized point_log (used for score history and the results screen) in sync.
     * All effect point grants must go through this.
     */
    protected function credit(array $challenge_data, int $player_id, int $points, string $label): array
    {
        $challenge_data['player_points'][$player_id] =
            ($challenge_data['player_points'][$player_id] ?? 0) + $points;

        $challenge_data['point_log'][] = [
            'player_id' => $player_id,
            'points' => $points,
            'type' => 'effect',
            'label' => $label,
        ];

        return $challenge_data;
    }

    /**
     * Helper: arm a "next time..." effect flag.
     */
    protected function arm(array $challenge_data, int $player_id, string $flag): array
    {
        $challenge_data['active_effects'][$player_id][$flag] = true;

        return $challenge_data;
    }

    /**
     * Helper: check if an effect flag is armed.
     */
    protected function isArmed(array $challenge_data, int $player_id, string $flag): bool
    {
        return ($challenge_data['active_effects'][$player_id][$flag] ?? false) === true;
    }

    /**
     * Helper: disarm a flag (one-shot effect consumed).
     */
    protected function disarm(array $challenge_data, int $player_id, string $flag): array
    {
        unset($challenge_data['active_effects'][$player_id][$flag]);

        return $challenge_data;
    }
}
