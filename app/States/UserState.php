<?php

namespace App\States;

use Thunk\Verbs\State;
use Illuminate\Support\Collection;

class UserState extends State
{
    public string $name;

    public string $email;

    public string $encrypted_password;

    public string $status;

    public Collection $player_ids;

    public Collection $is_admin_of_game_ids;

    public int $current_player_id;

    public int $current_game_id;

    public Collection $application_ids;

    public function __construct()
    {
        $this->player_ids = collect();
        $this->is_admin_of_game_ids = collect();
        $this->application_ids = collect();
    }

    public function currentPlayer(): ?PlayerState
    {
        return PlayerState::load($this->current_player_id);
    }

    public function currentGame(): ?GameState
    {
        return GameState::load($this->current_game_id);
    }

    public function isAdminOfGame(int $game_id): bool
    {
        return $this->is_admin_of_game_ids->contains($game_id);
    }
}
