<?php

namespace App\States;

use Illuminate\Support\Carbon;
use Thunk\Verbs\State;

class GameApplicationState extends State
{
    public int $user_id;

    public int $game_id;

    public string $status;

    public int $decided_by_id;

    public Carbon $decided_at;

    public int $player_id;
}
