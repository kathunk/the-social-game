<?php

namespace App\States;

use Thunk\Verbs\State;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PlayerState extends State
{
    public string $name;

    public string $status;

    public int $user_id;

    public int $game_id;

    public int $team_id;

    public Carbon $last_switched_team_at;

    public ?Collection $previous_team_ids;

    public function __construct()
    {
        $this->previous_team_ids = collect();
    }

    public function user(): UserState
    {
        return UserState::load($this->user_id);
    }

    public function game(): GameState
    {
        return GameState::load($this->game_id);
    }

    public function team(): TeamState
    {
        return TeamState::load($this->team_id);
    }
}
