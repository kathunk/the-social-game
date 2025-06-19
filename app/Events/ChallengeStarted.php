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

    public function applyToChallenge(ChallengeState $challenge)
    {
        $challenge->status = 'active';
        $challenge->challenge_data = $challenge->handler()->dataArrayForState();
    }

    public function applyToGame(GameState $state)
    {
        $state->current_challenge_id = $this->challenge_id;
        $this->state(ChallengeState::class)->handler()->onChallengeStarted($state);
    }

    public function handle(ChallengeState $state)
    {
        $game = Game::find($this->game_id);
        $game->update(['current_challenge_id' => $this->challenge_id]);

        Challenge::find($this->challenge_id)->update([
            'status' => 'active',
            'challenge_data' => $state->challenge_data,
        ]);
    }
}
