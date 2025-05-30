<?php

namespace App\Events;

use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasPlayer;
use App\Models\Challenge;
use App\Modifiers\Classes\BloodOaths;
use App\States\ChallengeState;
use App\States\GameState;
use Thunk\Verbs\Event;

class PlayerSubmittedPeckingOrderBallot extends Event
{
    use HasChallenge, HasGame, HasPlayer;

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

        $modifiers = $this->state(GameState::class)->modifiers();

        $is_blood_oath_game = $modifiers->filter(fn ($m) => $m->class_key === BloodOaths::key())->count() > 0;

        if (! $is_blood_oath_game) {
            return;
        }

        $oath_data = $modifiers->filter(fn ($m) => $m->class_key === BloodOaths::key())->first()->modifier_data;

        $has_buddy = $oath_data['pairs'][$this->player_id] ?? false;

        if (! $has_buddy) {
            return;
        }

        $buddy_id = $oath_data['pairs'][$this->player_id];

        $this->assert(
            $this->upvote_player_id !== $buddy_id,
            'You cannot upvote your blood oath partner'
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
