<?php

namespace App\Events;

use App\Events\Traits\HasActiveChallenge;
use App\Events\Traits\HasActiveGame;
use App\Models\Challenge;
use App\Models\Game;
use App\States\ChallengeState;
use App\States\GameState;
use Thunk\Verbs\Event;

class ChallengeEnded extends Event
{
    use HasActiveChallenge, HasActiveGame;

    public function applyToGame(GameState $state)
    {
        $this->state(ChallengeState::class)->handler()->onChallengeEnded($state);
        $state->current_challenge_id = null;

        $state->modifiers()->each(fn ($modifier) => $modifier->handler()->onChallengeEnded($state)
        );
    }

    public function applyToChallenge(ChallengeState $state)
    {
        $state->status = 'ended';
    }

    public function handle()
    {
        $game = Game::find($this->game_id);
        $game->current_challenge_id = null;
        $game->save();

        $type = $game->gameTemplate->type;

        if ($type === 'team') {
            $game->teams->each(function ($team) {
                $team->score = $team->state()->score();
                $team->hidden_score = $team->state()->score(include_hidden: true);
                $team->save();
            });
        }

        if ($type === 'individual') {
            $game->players->each(function ($player) {
                $player->score = $player->state()->score();
                $player->hidden_score = $player->state()->score(include_hidden: true);
                $player->save();
            });
        }

        $challenge = Challenge::find($this->challenge_id);
        $challenge->status = 'ended';
        $challenge->save();
        $challenge->updateModelWithStateData();
    }
}
