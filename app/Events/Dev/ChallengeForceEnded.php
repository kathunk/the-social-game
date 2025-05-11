<?php

namespace App\Events\Dev;

use Thunk\Verbs\Event;
use Thunk\Verbs\State;
use App\Models\Challenge;
use App\States\ChallengeState;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasActiveGame;

class ChallengeForceEnded extends Event
{
    use HasChallenge, HasActiveGame;

    public function validate()
    {
        $this->assert(
            config('app.env') === 'local',
            'This command is only available in local environment'
        );
    }

    public function applyToChallenge(ChallengeState $state)
    {
        $state->ends_at = now();
    }

    public function handle()
    {
        $challenge = Challenge::find($this->challenge_id);
        $challenge->ends_at = now();
        $challenge->save();
    }
}
