<?php

namespace App\Events;

use App\Models\Game;
use App\States\GameState;
use Carbon\Carbon;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class GameCreated extends Event
{
    #[StateId(GameState::class)]
    public ?int $game_id = null;

    public string $name;

    public string $template_class;

    public Carbon $starts_at;

    public Carbon $ends_at;

    public function applyToGame(GameState $game)
    {
        $game->name = $this->name;
        $game->status = 'upcoming';
        $game->template_class = $this->template_class;
        $game->starts_at = $this->starts_at;
        $game->ends_at = $this->ends_at;
    }

    public function handle()
    {
        Game::create([
            'id' => $this->game_id,
            'name' => $this->name,
            'status' => 'upcoming',
            'template_class' => $this->template_class,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
        ]);
    }
}
