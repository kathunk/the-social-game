<?php

namespace App\Events;

use Carbon\Carbon;
use App\Models\Game;
use Thunk\Verbs\Event;
use App\States\GameState;
use App\Models\GameTemplate;
use App\Events\Traits\HasUser;
use App\States\GameTemplateState;
use App\Events\Traits\HasGameTemplate;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

class GameCreated extends Event
{
    use HasUser, HasGameTemplate;

    #[StateId(GameState::class)]
    public ?int $game_id = null;

    public string $name;

    public Carbon $starts_at;

    public Carbon $ends_at;

    public function applyToGame(GameState $game)
    {
        $game->name = $this->name;
        $game->status = 'upcoming';
        $game->game_template_id = $this->game_template_id;
        $game->starts_at = $this->starts_at;

        $game->ends_at = $this->starts_at->addMinutes(
            $this->state(GameTemplateState::class)->durationOfAllChallengesInMinutes()
        );
    }

    public function handle()
    {
        Game::create([
            'id' => $this->game_id,
            'name' => $this->name,
            'status' => 'upcoming',
            'game_template_id' => $this->game_template_id,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->state(GameState::class)->ends_at,
        ]);
    }
}
