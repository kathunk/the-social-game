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
        $this->assert(
            ($data['current_actor_id'] ?? null) !== $this->claimant_id,
            'You cannot claim a forfeit on your own turn.'
        );
        $this->assert(
            $this->forfeited_at >= ($data['turn_started_at'] ?? 0) + ElephantMatch::TURN_SECONDS + ElephantMatch::FORFEIT_GRACE_SECONDS,
            'The turn timer has not expired yet.'
        );
    }

    public function apply(ChallengeState $challenge)
    {
        $data = $challenge->challenge_data;

        $data['match_status'] = 'complete';
        $data['victor_ids'] = [$this->claimant_id];

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
