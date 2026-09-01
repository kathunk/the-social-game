<?php

namespace App\Events\ElephantInTheRoom;

use App\Challenges\ElephantInTheRoom\Support\BoardLogic;
use App\Events\Traits\HasActiveChallenge;
use App\Events\Traits\HasGame;
use App\Models\Challenge;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class TileSlid extends Event
{
    use HasActiveChallenge, HasGame;

    // Actor id is a string ("bot" or a player id) rather than a Player state
    // binding, because the bot opponent has no Player row. Turn identity is
    // enforced against challenge_data below.
    public string $actor_id;

    public int $entry_space;

    public string $direction;

    public string $client_move_id;

    public function validate()
    {
        // Read from state (not model) so events fired earlier in the same batch
        // are visible to validation
        $data = $this->state(ChallengeState::class)->challenge_data;

        $this->assert(($data['match_status'] ?? null) === 'active', 'The match is over.');
        // Bot games can't start until the human has picked an opponent — the
        // client enforces this with the picker overlay, but only this assert
        // makes it binding
        $this->assert(
            ! ($data['is_bot_game'] ?? false) || ($data['bot_difficulty'] ?? null) !== null,
            'Pick a difficulty first.'
        );
        $this->assert(in_array($this->actor_id, $data['actor_order'] ?? [], true), 'Unknown player.');
        $this->assert(($data['current_actor_id'] ?? null) === $this->actor_id, 'It is not your turn.');
        $this->assert(($data['phase'] ?? null) === 'tile', 'You must move the elephant first.');
        $this->assert(($data['hands'][$this->actor_id] ?? 0) > 0, 'No tiles left in hand.');
        $this->assert(BoardLogic::isSlideConfig($this->entry_space, $this->direction), 'Invalid slide.');
        $this->assert(
            ! BoardLogic::slideIsBlockedByElephant($data['board'], $data['elephant_space'], $this->entry_space, $this->direction),
            'The elephant blocks that slide.'
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

        $result = BoardLogic::applySlide($data['board'], $this->entry_space, $this->direction, $this->actor_id);
        $data['board'] = $result['board'];
        $data['hands'][$this->actor_id]--;

        if ($result['pushed_off_owner'] !== null) {
            $data['hands'][$result['pushed_off_owner']]++;
        }

        $data['phase'] = 'move';

        // Victory is checked for BOTH actors after every tile placement — a
        // slide can push your opponent's tiles into their winning shape, and
        // both can win at once (a draw where both are recorded as victors)
        $victors = [];
        $winning_spaces = [];
        foreach ($data['actor_order'] as $actor) {
            $spaces = BoardLogic::winningSpaces($data['board'], $actor, $data['victory_shape']);
            if ($spaces !== []) {
                $victors[] = $actor;
                $winning_spaces = array_values(array_unique(array_merge($winning_spaces, $spaces)));
            }
        }

        $data['last_seq'] = ($data['last_seq'] ?? 0) + 1;
        $data['moves'][] = [
            'seq' => $data['last_seq'],
            'actor_id' => $this->actor_id,
            'type' => 'tile',
            'entry_space' => $this->entry_space,
            'direction' => $this->direction,
            'pushed_off_owner' => $result['pushed_off_owner'],
            'client_move_id' => $this->client_move_id,
        ];

        $run = BoardLogic::trailingSlideRuns($data['moves'])[$this->actor_id] ?? null;

        if ($victors !== []) {
            $data['match_status'] = 'complete';
            $data['victor_ids'] = $victors;
            $data['winning_spaces'] = $winning_spaces;
        } elseif (($run['count'] ?? 0) > BoardLogic::MAX_SLIDE_REPEATS) {
            // The same slide three times in a row forfeits the match to the
            // opponent (a slide that completes a shape wins first, above)
            $other = collect($data['actor_order'])->first(fn ($actor) => $actor !== $this->actor_id);

            $data['match_status'] = 'complete';
            $data['victor_ids'] = [$other];
            $data['repetition_loss_by'] = $this->actor_id;
        } elseif (collect($data['hands'])->every(fn ($hand) => $hand === 0)) {
            // Both players out of tiles with no winner: the match is a dead draw
            $data['match_status'] = 'complete';
        }

        $challenge->challenge_data = $data;
    }

    public function handle(ChallengeState $state)
    {
        Challenge::find($this->challenge_id)?->update([
            'challenge_data' => $state->challenge_data,
        ]);
    }
}
