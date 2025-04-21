<?php

namespace App\Events\Traits;

use App\States\ChallengeState;
use App\States\GameState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

trait HasChallenge
{
    #[StateId(ChallengeState::class)]
    public int $challenge_id;

    public function validateChallenge()
    {
        $this->assert(
            $this->state(GameState::class)->challenge_ids->contains($this->challenge_id),
            'Challenge is not in game'
        );
    }
}
