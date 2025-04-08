<?php

namespace App\Events;

use App\Models\User;
use App\Models\Player;
use Thunk\Verbs\Event;
use App\States\GameState;
use App\States\UserState;
use App\States\PlayerState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

class UserAdmittedToGame extends Event
{
    #[StateId(PlayerState::class)]
    public ?int $player_id = null;

    #[StateId(UserState::class)]
    public int $user_id;

    #[StateId(GameState::class)]
    public int $game_id;

    public int $admin_id;

    public function validate()
    {
        $this->assert(
            $this->state(GameState::class)->admin_ids->contains($this->admin_id),
            'Admin is not an admin of this game',
        );

        $this->assert(
            ! $this->state(GameState::class)->user_ids->contains($this->user_id),
            'User is already a player of this game',
        );
    }

    public function applyToGame(GameState $game)
    {
        $game->user_ids->push($this->user_id);
        $game->player_ids->push($this->player_id);
    }

    public function applyToUser(UserState $user)
    {
        $user->current_game_id = $this->game_id;
        $user->current_player_id = $this->player_id;
    }

    public function applyToPlayer(PlayerState $player)
    {
        $player->user_id = $this->user_id;
        $player->game_id = $this->game_id;
        $player->status = 'active';
    }

    public function handle()
    {
        Player::create([
            'id' => $this->player_id,
            'user_id' => $this->user_id,
            'game_id' => $this->game_id,
            'status' => 'active',
        ]);

        $user = User::find($this->user_id);
        $user->current_game_id = $this->game_id;
        $user->current_player_id = $this->player_id;
        $user->status = 'accepted';
        $user->save();
    }
}
