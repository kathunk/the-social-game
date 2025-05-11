<?php

namespace App\Events\Dev;

use Thunk\Verbs\Event;
use App\Models\Challenge;
use App\States\ChallengeState;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasActiveGame;

class ChallengeForceStarted extends Event
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
        $state->starts_at = now();
    }

    public function handle()
    {
        $challenge = Challenge::find($this->challenge_id);
        $challenge->starts_at = now();
        $challenge->save();
    }
}
