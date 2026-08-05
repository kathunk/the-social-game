<?php

namespace App\Challenges\MorningRoutine;

use App\Challenges\BaseChallengeClass;
use App\Models\Player;

/**
 * Post-round recap for MorningRoutine: shows every player's inventory and
 * itemized point ledger so the table can reconstruct how the scores happened.
 * Runs as its own (actionless) challenge after the round ends, reading the
 * ended round's challenge_data.
 */
class MorningRoutineResults extends BaseChallengeClass
{
    const NAME = 'Morning Routine Results';

    const DESCRIPTION = 'See what everyone did this morning.';

    const TYPE = 'individual';

    const HIDE_SCOREBOARD = false;

    public static function key(): string
    {
        return 'morning_routine_results';
    }

    public function frontendComponent(Player $player): array
    {
        $round = $this->challenge->game->challenges
            ->where('class_key', MorningRoutineRound::key())
            ->where('status', 'ended')
            ->sortByDesc('id')
            ->first();

        return $this->form()
            ->morningRoutineResults(
                player: $player,
                round_data: $round?->challenge_data ?? [],
                players: $this->challenge->game->players,
            )
            ->build();
    }
}
