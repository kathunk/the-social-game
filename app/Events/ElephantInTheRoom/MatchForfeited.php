<?php

namespace App\Events\ElephantInTheRoom;

use App\Challenges\ElephantInTheRoom\ElephantMatch;
use App\Events\Traits\HasActiveChallenge;
use App\Events\Traits\HasGame;
use App\Models\Challenge;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

/**
 * Lazy claim-forfeit: when the current player's turn timer runs out, the
 * WAITING player claims the win. The server only validates the timestamps —
 * no scheduled command needed.
 */
class MatchForfeited extends Event
{
    use HasActiveChallenge, HasGame;

    public string $claimant_id;

    // Supplied by the caller so event replay is deterministic
    public int $forfeited_at;

    public function validate()
    {
        $data = $this->state(ChallengeState::class)->challenge_data;

        $this->assert(($data['match_status'] ?? null) === 'active', 'The match is over.');
        $this->assert(in_array($this->claimant_id, $data['actor_order'] ?? [], true), 'Unknown player.');

        if ($data['is_bot_game'] ?? false) {
            // The human runs the only client, so the only claimable timeout
            // is their own — the bot always moves promptly when a client is
            // present, so it can never legitimately be overdue
            $this->assert(
                ($data['current_actor_id'] ?? null) === $this->claimant_id,
                'The Bot cannot time out.'
            );
        } else {
            $this->assert(
                ($data['current_actor_id'] ?? null) !== $this->claimant_id,
                'You cannot claim a forfeit on your own turn.'
            );
        }
        $this->assert(
            $this->forfeited_at >= ($data['turn_started_at'] ?? 0) + ElephantMatch::TURN_SECONDS + ElephantMatch::FORFEIT_GRACE_SECONDS,
            'The turn timer has not expired yet.'
        );
    }

    public function apply(ChallengeState $challenge)
    {
        $data = $challenge->challenge_data;

        // The victor is whoever is NOT on the clock: the waiting player in a
        // 2-player game (the claimant), the bot when the human times out
        $victor = collect($data['actor_order'])
            ->first(fn ($actor) => $actor !== ($data['current_actor_id'] ?? null));

        $data['match_status'] = 'complete';
        $data['victor_ids'] = [$victor];

        $data['last_seq'] = ($data['last_seq'] ?? 0) + 1;
        $data['moves'][] = [
            'seq' => $data['last_seq'],
            'actor_id' => $this->claimant_id,
            'type' => 'forfeit',
            'client_move_id' => 'forfeit-'.$data['last_seq'],
        ];

        $challenge->challenge_data = $data;
    }

    public function handle(ChallengeState $state)
    {
        Challenge::find($this->challenge_id)?->update([
            'challenge_data' => $state->challenge_data,
        ]);
    }
}
