<?php

namespace App\Events;

use App\Events\Traits\HasActiveGame;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasPlayerOnTeam;
use App\Models\Challenge;
use App\States\ChallengeState;
use App\States\PlayerState;
use Thunk\Verbs\Event;

class PlayerSubmittedPlayDirty extends Event
{
    use HasActiveGame, HasChallenge, HasPlayerOnTeam;

    public string $message;

    public function validate(PlayerState $player, ChallengeState $challenge)
    {
        $this->assert(
            $player->team_id === $this->team_id,
            'Player is not on team'
        );

        $this->assert(
            collect($challenge->challenge_data['team_voters'][$player->team_id])->doesntContain($player->id),
            'Player has already voted for this team'
        );
    }

    public function applyToChallenge(ChallengeState $challenge)
    {
        $challenge->challenge_data['team_voters'][$this->team_id][] = $this->player_id;
    }

    public function handle()
    {
        $challenge = Challenge::find($this->challenge_id);
        $challenge->challenge_data = $this->state(ChallengeState::class)->challenge_data;
        $challenge->save();
    }
}
