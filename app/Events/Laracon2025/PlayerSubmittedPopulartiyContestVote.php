<?php

namespace App\Events\Laracon2025;

use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasPlayer;
use App\Models\Challenge;
use App\States\ChallengeState;
use App\States\GameState;
use Thunk\Verbs\Event;

class PlayerSubmittedPopulartiyContestVote extends Event
{
    use HasChallenge, HasGame, HasPlayer;

    public int $downvote_team_id;

    public int $upvote_team_id;

    public function validate()
    {
        $this->assert(
            $this->state(GameState::class)->team_ids->contains($this->downvote_team_id),
            'Downvote team is not in the game'
        );

        $this->assert(
            $this->state(GameState::class)->team_ids->contains($this->upvote_team_id),
            'Upvote team is not in the game'
        );
    }

    public function apply(ChallengeState $challenge)
    {
        $challenge->challenge_data['votes'][$this->player_id]['downvote_team_id'] = $this->downvote_team_id;
        $challenge->challenge_data['votes'][$this->player_id]['upvote_team_id'] = $this->upvote_team_id;
    }

    public function handle()
    {
        Challenge::find($this->challenge_id)->updateModelWithStateData();
    }
}
