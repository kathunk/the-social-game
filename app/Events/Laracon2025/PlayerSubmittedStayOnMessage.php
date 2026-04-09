<?php

namespace App\Events\Laracon2025;

use App\Events\Traits\HasActiveGame;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasPlayerOnTeam;
use App\Models\Challenge;
use App\States\ChallengeState;
use App\States\PlayerState;
use Thunk\Verbs\Event;

class PlayerSubmittedStayOnMessage extends Event
{
    use HasActiveGame, HasChallenge, HasPlayerOnTeam;

    public string $message;

    public function validate()
    {
        $this->assert(
            $this->state(PlayerState::class)->team_id === $this->team_id,
            'Player is not on team'
        );
    }

    public function applyToChallenge(ChallengeState $challenge)
    {
        $challenge->challenge_data[$this->team_id][$this->player_id] = $this->message;
    }

    public function handle()
    {
        $challenge = Challenge::find($this->challenge_id);
        $challenge->challenge_data = $this->state(ChallengeState::class)->challenge_data;
        $challenge->save();
    }
}
