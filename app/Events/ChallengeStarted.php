<?php

namespace App\Events;

use App\Events\Traits\HasActiveGame;
use App\Events\Traits\HasChallenge;
use App\Models\Challenge;
use App\Models\Game;
use App\States\ChallengeState;
use App\States\GameState;
use Thunk\Verbs\Event;

class ChallengeStarted extends Event
{
    use HasActiveGame, HasChallenge;

    public function validate()
    {
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
