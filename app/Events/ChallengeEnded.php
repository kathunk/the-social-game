<?php

namespace App\Events;

use App\Models\Challenge;
use App\Models\Game;
use App\States\ChallengeState;
use App\States\GameState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class ChallengeEnded extends Event
{
    #[StateId(ChallengeState::class)]
    public int $challenge_id;

    #[StateId(GameState::class)]
    public int $game_id;

    public function validate()
    {
        $this->assert(
            $this->state(ChallengeState::class)->status === 'active',
            'Challenge is not active'
        );

        $this->assert(
            $this->state(GameState::class)->challenge_ids->contains($this->challenge_id),
            'Challenge is not in the game'
        );

        $this->assert(
            $this->state(ChallengeState::class)->game_id === $this->game_id,
            'Challenge is not in the game'
        );
    }

    public function applyToGame(GameState $state)
    {
        $state->current_challenge_id = null;
    }

    public function applyToChallenge(ChallengeState $state)
    {
        $state->status = 'ended';
    }

    public function handle()
    {
        Game::find($this->game_id)->update(['current_challenge_id' => null]);
        Challenge::find($this->challenge_id)->update(['status' => 'ended']);
    }
}
