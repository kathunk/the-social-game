<?php

namespace App\Events\PeckingOrder;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerChosePointsOrHiddenPoints extends Event
{
    use HasActivePlayer, HasChallenge, HasGame;

    public string $choice;

    public function validate()
    {
        $this->assert(
            in_array($this->choice, ['points', 'hidden']),
            'Invalid choice',
        );
    }

    public function apply(ChallengeState $challenge)
    {
        $challenge->challenge_data['choices'][$this->player_id] = $this->choice;
    }

    public function handle()
    {
        $this->challenge()->updateModelWithStateData();
    }
}
