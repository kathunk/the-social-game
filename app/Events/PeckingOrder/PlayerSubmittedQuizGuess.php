<?php

namespace App\Events\PeckingOrder;

use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasPlayer;
use App\Models\Challenge;
use App\States\ChallengeState;
use Thunk\Verbs\Event;

class PlayerSubmittedQuizGuess extends Event
{
    use HasChallenge, HasGame, HasPlayer;

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
