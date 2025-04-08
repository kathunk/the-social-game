<?php

namespace Tests;

use App\Models\Game;
use App\Models\User;
use App\Models\Player;
use App\Events\GameCreated;
use App\Events\UserCreated;
use App\Events\UserAdmittedToGame;
use App\Events\UserPromotedToAdmin;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public Game $game;
    public User $admin;

    public function createGame(?string $name = null)
    {
        $game_id = GameCreated::fire(
            name: $name ?? fake()->name(),
        )->game_id;

        $this->game = Game::find($game_id);

        return $this->game;
    }

    public function createUser(?string $name = null, ?string $email = null, ?string $encrypted_password = null, ?bool $is_admin = false)
    {
        $user_id = UserCreated::fire(
            name: $name ?? fake()->name(),
            email: $email ?? fake()->email(),
            encrypted_password: $encrypted_password ?? fake()->password(),
            is_admin: $is_admin,
        )->user_id;

        $user = User::find($user_id);

        if (! $is_admin) {
            UserPromotedToAdmin::fire(
                user_id: $user_id,
                game_id: $this->game->id,
            );

            $this->admin = $user;
        }

        return $user;
    }

    public function createAdmin()
    {
        $this->admin = $this->createUser(is_admin: true);

        return $this->admin;
    }

    public function createPlayer(?User $user = null, ?Game $game = null)
    {
        $user = $user ?? $this->createUser();
        $game = $game ?? $this->game;

        $player_id = UserAdmittedToGame::fire(
            user_id: $user->id,
            admin_id: $this->admin->id,
            game_id: $game->id,
        )->player_id;

        return Player::find($player_id);
    }
}