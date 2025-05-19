<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\Events\Traits\HasGame;
use App\States\ChallengeState;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasActivePlayer;

class PlayerChoseSafetyOrDanger extends Event
{
    use HasActivePlayer, HasChallenge, HasGame;

    public string $choice;

    public function validate()
    {
        $this->assert(
            in_array($this->choice, ['safety', 'danger']),
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
