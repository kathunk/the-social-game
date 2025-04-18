<?php

namespace App\Events;

use App\Models\Game;
use Thunk\Verbs\Event;
use App\Models\Challenge;
use App\States\GameState;
use App\States\ChallengeState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

class ChallengeStarted extends Event
{
    #[StateId(ChallengeState::class)]
    public int $challenge_id;

    #[StateId(GameState::class)]
    public int $game_id;

    public function validate()
    {
        $this->assert(
            $this->state(GameState::class)->status === 'active',
            'Game is not active'
        );

        $this->assert(
            $this->state(ChallengeState::class)->status === 'upcoming',
            'Challenge is not upcoming'
        );

        $this->assert(
            $this->state(GameState::class)->challenge_ids->contains($this->challenge_id),
            'Challenge is not in the game'
        );

        $this->assert(
            $this->state(ChallengeState::class)->game_id === $this->game_id,
            'Challenge is not in the game'
        );

        $this->assert(
            $this->state(GameState::class)->challenges()->filter(fn ($c) => $c->status === 'active')->count() === 0,
            'Cannot have more than one active challenge'
        );
    }

    public function applyToGame(GameState $state)
    {
        $state->current_challenge_id = $this->challenge_id;
    }

    public function applyToChallenge(ChallengeState $state)
    {
        $state->status = 'active';
    }

    public function handle()
    {
        Game::find($this->game_id)->update(['current_challenge_id' => $this->challenge_id]);
        Challenge::find($this->challenge_id)->update(['status' => 'active']);
    }
}
