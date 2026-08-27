<?php

namespace App\Events\ElephantInTheRoom;

use App\Challenges\ElephantInTheRoom\ElephantMatch;
use App\Events\Traits\HasActiveChallenge;
use App\Events\Traits\HasGame;
use App\Models\Challenge;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class BotDifficultySet extends Event
{
    use HasActiveChallenge, HasGame;

    public string $actor_id;

    public string $difficulty;

    public function validate()
    {
        $data = $this->state(ChallengeState::class)->challenge_data;

        $this->assert(($data['match_status'] ?? null) === 'active', 'The match is over.');
        $this->assert($data['is_bot_game'] ?? false, 'This is not a bot game.');
        $this->assert(
            in_array($this->actor_id, $data['actor_order'] ?? [], true)
                && $this->actor_id !== ElephantMatch::BOT_ID,
            'Unknown player.'
        );
        $this->assert(
            in_array($this->difficulty, ElephantMatch::BOT_DIFFICULTIES, true),
            'Unknown difficulty.'
        );
        $this->assert(($data['bot_difficulty'] ?? null) === null, 'Difficulty is already set.');
        $this->assert(($data['moves'] ?? []) === [], 'The match has already started.');
    }

    public function apply(ChallengeState $challenge)
    {
        $data = $challenge->challenge_data;

        $data['bot_difficulty'] = $this->difficulty;

        $challenge->challenge_data = $data;
    }

    public function handle(ChallengeState $state)
    {
        Challenge::find($this->challenge_id)?->update([
            'challenge_data' => $state->challenge_data,
        ]);
    }
}
