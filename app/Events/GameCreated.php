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

    public string $template_class;

    public function applyToGame(GameState $game)
    {
        $game->name = $this->name;
        $game->status = 'active';
        $game->template_class = $this->template_class;
    }

    public function handle()
    {
        Game::create([
            'id' => $this->game_id,
            'name' => $this->name,
            'status' => 'active',
            'template_class' => $this->template_class,
        ]);
    }
}
