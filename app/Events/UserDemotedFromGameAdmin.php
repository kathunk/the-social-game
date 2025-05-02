<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasUser;
use App\Models\Game;
use App\Models\User;
use App\States\GameState;
use App\States\UserState;
use Thunk\Verbs\Event;

class UserDemotedFromGameAdmin extends Event
{
    use HasGame, HasUser;

    public int $admin_id;

    public function validate()
    {
        $this->assert(
            $this->state(GameState::class)->admin_ids->contains($this->admin_id),
            'Admin ID is not an admin of this game',
        );

        $this->assert(
            $this->state(GameState::class)->admin_ids->contains($this->user_id),
            'User is not an admin of this game',
        );

        $this->assert(
            $this->state(UserState::class)->is_admin_of_game_ids->contains($this->game_id),
            'User is not an admin of this game',
        );
    }

    public function applyToUser(UserState $user)
    {
        $user->is_admin_of_game_ids = $user->is_admin_of_game_ids->reject(fn ($id) => $id === $this->game_id);
    }

    public function applyToGame(GameState $game)
    {
        $game->admin_ids = $game->admin_ids->reject(fn ($id) => $id === $this->user_id);
    }

    public function handle()
    {
        $user = User::find($this->user_id);
        $game = Game::find($this->game_id);
        $user->adminGames()->detach($game);
        $user->save();
    }
}
