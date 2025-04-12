<?php

namespace App\States;

use Thunk\Verbs\State;
use Illuminate\Support\Carbon;

class GameApplicationState extends State
{
    public int $user_id;

    public int $game_id;

    public string $status;

    public int $decided_by_id;

    public Carbon $decided_at;

    public int $player_id;
}
