<?php

namespace App\Events\Traits;

use App\States\ChallengeState;
use App\States\GameState;

trait HasActiveChallenge
{
    use HasChallenge;

    public function validateActiveChallenge()
    {
        $this->assert(
            $this->state(GameState::class)->current_challenge_id === $this->challenge_id,
            'Challenge is not current challenge for game'
        );

        $this->assert(
            $this->state(ChallengeState::class)->status === 'active',
            'Challenge is not active'
        );
    }
}
