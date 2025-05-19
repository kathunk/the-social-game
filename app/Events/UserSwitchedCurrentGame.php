<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasUser;
use App\States\GameState;
use App\States\UserState;
use Thunk\Verbs\Event;

class UserSwitchedCurrentGame extends Event
{
    use HasGame, HasPlayer, HasUser;

    public function validate()
    {
        $this->assert(
            $this->state(GameState::class)->player_ids->contains($this->player_id),
            'Player is not in the game',
        );
    }

    public function applyToUser(UserState $user)
    {
        $user->current_game_id = $this->game_id;
        $user->current_player_id = $this->player_id;
    }

    public function handle()
    {
        $this->user()->update([
            'current_game_id' => $this->game_id,
            'current_player_id' => $this->player_id,
        ]);
    }
}
