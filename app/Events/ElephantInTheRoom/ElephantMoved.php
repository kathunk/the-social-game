<?php

namespace App\Events\ElephantInTheRoom;

use App\Challenges\ElephantInTheRoom\Support\BoardLogic;
use App\Events\Traits\HasActiveChallenge;
use App\Events\Traits\HasGame;
use App\Models\Challenge;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class ElephantMoved extends Event
{
    use HasActiveChallenge, HasGame;

    public string $actor_id;

    public int $to_space;

    public string $client_move_id;

    // Supplied by the caller (not now()) so event replay is deterministic;
    // becomes the new turn's turn_started_at for the forfeit timer
    public int $moved_at;

    public function validate()
    {
        $data = $this->state(ChallengeState::class)->challenge_data;

        $this->assert(($data['match_status'] ?? null) === 'active', 'The match is over.');
        $this->assert(($data['current_actor_id'] ?? null) === $this->actor_id, 'It is not your turn.');
        $this->assert(($data['phase'] ?? null) === 'move', 'You must place a tile first.');
        $this->assert(
            in_array($this->to_space, BoardLogic::validElephantMoves($data['elephant_space']), true),
            'The elephant can only move to an adjacent space or stay put.'
        );
        $this->assert($this->client_move_id !== '', 'Missing move id.');
        $this->assert(
            ! in_array($this->client_move_id, array_column($data['moves'] ?? [], 'client_move_id'), true),
            'Duplicate move.'
        );
    }

    public function apply(ChallengeState $challenge)
    {
        $data = $challenge->challenge_data;

        $data['elephant_space'] = $this->to_space;
        $data['phase'] = 'tile';

        // The turn passes to the other actor — unless their hand is empty,
        // in which case the current actor keeps going until a pushed-off
        // tile refills the opponent's hand
        $other = collect($data['actor_order'])->first(fn ($actor) => $actor !== $this->actor_id);
        $data['current_actor_id'] = ($data['hands'][$other] ?? 0) > 0 ? $other : $this->actor_id;
        $data['turn_started_at'] = $this->moved_at;

        $data['last_seq'] = ($data['last_seq'] ?? 0) + 1;
        $data['moves'][] = [
            'seq' => $data['last_seq'],
            'actor_id' => $this->actor_id,
            'type' => 'elephant',
            'to_space' => $this->to_space,
            'client_move_id' => $this->client_move_id,
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
