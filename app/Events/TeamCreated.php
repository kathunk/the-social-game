<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Models\Team;
use App\States\GameState;
use App\States\TeamState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class TeamCreated extends Event
{
    use HasGame;

    #[StateId(TeamState::class)]
    public ?int $team_id = null;

    public string $name;

    public function applyToGame(GameState $game)
    {
        $game->team_ids->push($this->team_id);
    }

    public function applyToTeam(TeamState $team)
    {
        $team->name = $this->name;
        $team->game_id = $this->game_id;
    }

    public function handle()
    {
        Team::create([
            'id' => $this->team_id,
            'game_id' => $this->game_id,
            'name' => $this->name,
            'score' => 0,
        ]);
    }
}
