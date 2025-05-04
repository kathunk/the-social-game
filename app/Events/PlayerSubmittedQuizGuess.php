<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\Models\Challenge;
use App\Events\Traits\HasGame;
use App\States\ChallengeState;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasChallenge;

class PlayerSubmittedQuizGuess extends Event
{
    use HasPlayer, HasChallenge, HasGame;

    public array $guess;

    public function apply(ChallengeState $challenge)
    {
        $challenge->challenge_data['quiz_submissions'][$this->player_id] = $this->guess;
    }

    public function handle()
    {
        Challenge::find($this->challenge_id)->updateModelWithStateData();
    }
}
