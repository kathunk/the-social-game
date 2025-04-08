<?php

namespace App\Events;

use App\Models\Game;
use Thunk\Verbs\Event;
use App\States\GameState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

class GameCreated extends Event
{
    #[StateId(GameState::class)]
    public ?int $game_id = null;

    public string $name;

    public int $admin_id;

    public function applyToGame(GameState $game)
    {
        $game->name = $this->name;
        $game->status = 'active';
    }

    public function handle()
    {
        Game::create([
            'id' => $this->game_id,
            'name' => $this->name,
            'status' => 'active',
        ]);
    }
}
