<?php

namespace App\States;

use Thunk\Verbs\State;
use Illuminate\Support\Collection;

class GameState extends State
{
    public string $name;

    public string $status;

    public Collection $user_ids;

    public Collection $player_ids;

    public Collection $admin_ids;

    public function __construct()
    {
        $this->player_ids = collect();
        $this->admin_ids = collect();
        $this->user_ids = collect();
    }

    public function players()
    {
        return $this->player_ids->map(fn (int $player_id) => PlayerState::load($player_id));
    }

    public function admins()
    {
        return $this->admin_ids->map(fn (int $admin_id) => UserState::load($admin_id));
    }

    public function users()
    {
        return $this->user_ids->map(fn (int $user_id) => UserState::load($user_id));
    }
}
