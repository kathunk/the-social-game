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

    public function createPlayer(?User $user = null, ?User $admin = null, ?Game $game = null)
    {
        $user = $user ?? $this->createUser();
        $game = $game ?? $this->game;
        $admin = $admin ?? $this->admin;

        $player = $user->admitToGame($game, $admin);

        return $player;
    }
}