<?php

namespace App\Events;

use App\Models\Challenge;
use App\States\ChallengeState;
use App\States\GameState;
use App\States\PlayerState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class PlayerSubmittedStayOnMessage extends Event
{
    #[StateId(PlayerState::class)]
    public int $player_id;

    #[StateId(ChallengeState::class)]
    public int $challenge_id;

    public int $team_id;

    #[StateId(GameState::class)]
    public int $game_id;

    public string $message;

    // @todo this is all boilerplate for any kind of challenge submission. probably extract it into a HasChallenge trait?
    public function validate()
    {
        $this->assert(
            $this->state(PlayerState::class)->team_id === $this->team_id,
            'Player is not on team'
        );

        $this->assert(
            $this->state(GameState::class)->challenge_ids->contains($this->challenge_id),
            'Challenge is not in game'
        );

        $this->assert(
            $this->state(GameState::class)->current_challenge_id === $this->challenge_id,
            'Challenge is not current challenge for game'
        );

        $this->assert(
            $this->state(ChallengeState::class)->status === 'active',
            'Challenge is not active'
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
