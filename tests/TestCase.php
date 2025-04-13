<?php

namespace Tests;

use App\Models\Game;
use App\Models\User;
use App\Models\Player;
use App\GameTemplates\Laracon2025;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public Game $game;
    public User $admin;
    public Player $player;

    public function createGame()
    {
        $this->game = (new Laracon2025())->createGame();

        return $this->game;
    }

    public function createPlayer()
    {
        if (! isset($this->game)) {
            $this->createGame();
        }

        if (! isset($this->admin)) {
            $this->admin = User::fromTemplate(name: 'admin', email: 'admin@example.com', encrypted_password: 'password')
                ->promoteToAdmin($this->game);
        }

        $this->player = User::fromTemplate(
            name: fake()->name(),
            email: fake()->email(),
            encrypted_password: 'password',
            game: $this->game,
        )->admitToGame($this->game, $this->admin);
    }
}