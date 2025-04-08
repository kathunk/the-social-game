<?php

namespace App\States;

use Thunk\Verbs\State;

class PlayerState extends State
{
    public string $name;

    public string $status;

    public int $user_id;

    public int $game_id;

    public function user(): UserState
    {
        return UserState::load($this->user_id);
    }

    public function game(): GameState
    {
        return GameState::load($this->game_id);
    }
}
