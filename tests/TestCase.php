<?php

namespace Tests;

use App\GameTemplates\Laracon2025;
use App\Models\Game;
use App\Models\Player;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;

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
