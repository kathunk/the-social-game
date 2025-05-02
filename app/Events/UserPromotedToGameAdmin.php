<?php

namespace App\Events;

use App\Models\Game;
use App\Models\User;
use Thunk\Verbs\Event;
use App\States\GameState;
use App\States\UserState;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasUser;

class UserPromotedToGameAdmin extends Event
{
    use HasGame, HasUser;

    public int $admin_id;

    public function validate()
    {
        $this->assert(
            ! $this->state(GameState::class)->admin_ids->contains($this->user_id),
            'User is already an admin of this game',
        );

        $this->assert(
            ! $this->state(UserState::class)->is_admin_of_game_ids->contains($this->game_id),
            'User is already an admin of this game',
        );
    }

    public function applyToUser(UserState $user)
    {
        $user->is_admin_of_game_ids->push($this->game_id);
        $user->current_game_id = $this->game_id;
    }

    public function applyToGame(GameState $game)
    {
        $game->admin_ids->push($this->user_id);
    }

    public function handle()
    {
        $user = User::find($this->user_id);
        $game = Game::find($this->game_id);
        $user->adminGames()->attach($game);
        $user->current_game_id = $game->id;
        $user->save();
    }
}
