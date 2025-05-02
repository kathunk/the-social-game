<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasGameApplication;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasUser;
use App\Models\GameApplication;
use App\Models\Player;
use App\Models\User;
use App\States\GameApplicationState;
use App\States\GameState;
use App\States\PlayerState;
use App\States\UserState;
use Thunk\Verbs\Event;

class PlayerRemovedFromGame extends Event
{
    use HasGame, HasGameApplication, HasPlayer, HasUser;

    public int $admin_id;

    public function validate()
    {
        $this->assert(
            $this->state(GameState::class)->admin_ids->contains($this->admin_id),
            'Admin is not an admin of this game',
        );

        $this->assert(
            $this->state(GameState::class)->player_ids->contains($this->player_id),
            'Player is not a player of this game',
        );
    }

    public function applyToGame(GameState $game)
    {
        $game->removed_player_ids->push($this->player_id);
        $game->player_ids = $game->player_ids->reject(fn ($player_id) => $player_id === $this->player_id);
        $game->admin_ids = $game->admin_ids->reject(fn ($admin_id) => $admin_id === $this->user_id);
    }

    public function applyToUser(UserState $user)
    {
        $user->current_game_id = null;
        $user->current_player_id = null;
    }

    public function applyToApplication(GameApplicationState $application)
    {
        $application->status = 'rejected';
    }

    public function applyToPlayer(PlayerState $player)
    {
        $player->status = 'removed';
    }

    public function handle()
    {
        $player = Player::find($this->player_id);

        $player->status = 'removed';

        $player->save();

        $user = User::find($this->user_id);
        $user->current_game_id = null;
        $user->current_player_id = null;
        $user->adminGames()->detach($this->game_id);
        $user->save();

        $application = GameApplication::find($this->application_id);
        $application->status = 'rejected';
        $application->save();
    }
}
