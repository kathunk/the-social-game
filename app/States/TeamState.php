<?php

namespace App\States;

use Illuminate\Support\Collection;
use Thunk\Verbs\State;

class TeamState extends State
{
    public string $name;

    public int $game_id;

    public Collection $score_history;

    public Collection $player_ids;

    public function __construct()
    {
        $this->score_history = collect();
        $this->player_ids = collect();
    }

    public function addToScoreHistory(int $points, string $title, string $description)
    {
        $this->score_history->push([
            'points' => $points,
            'title' => $title,
            'description' => $description,
        ]);
    }

    public function score(): int
    {
        return $this->score_history->sum();
    }

    public function game()
    {
        return GameState::load($this->game_id);
    }

    public function players(): Collection
    {
        return $this->player_ids->map(fn (int $player_id) => PlayerState::load($player_id));
    }
}
