<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\Models\Challenge;
use App\States\GameState;
use App\Events\Traits\HasGame;
use App\States\ChallengeState;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasChallenge;

class PlayerSubmittedPeckingOrderBallot extends Event
{
    use HasPlayer, HasGame, HasChallenge;

    public int $downvote_player_id;

    public int $upvote_player_id;

    public function validate()
    {
        $this->assert(
            $this->state(GameState::class)->player_ids->contains($this->downvote_player_id),
            'Downvote player is not in the game'
        );

        $this->assert(
            $this->state(GameState::class)->player_ids->contains($this->upvote_player_id),
            'Upvote player is not in the game'
        );
    }

    public function apply(ChallengeState $challenge)
    {
        $challenge->challenge_data['votes'][$this->player_id]['downvote_player_id'] = $this->downvote_player_id;
        $challenge->challenge_data['votes'][$this->player_id]['upvote_player_id'] = $this->upvote_player_id;
    }

    public function handle()
    {
        Challenge::find($this->challenge_id)->updateModelWithStateData();
    }
}
