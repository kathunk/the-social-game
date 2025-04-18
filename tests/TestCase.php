<?php

namespace Tests;

use App\Models\Game;
use App\Models\User;
use App\Models\Player;
use Illuminate\Support\Carbon;
use App\GameTemplates\Laracon2025;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public Game $game;
    public User $admin;
    public Player $player;

    public function createGame(?Carbon $starts_at = null)
    {
        $this->game = (new Laracon2025($starts_at))->createGame();

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