<?php

namespace App\States;

use Illuminate\Support\Collection;
use Thunk\Verbs\State;

class TeamState extends State
{
    public string $name;

    public int $game_id;

    public array $score_history;

    public Collection $player_ids;

    public function __construct()
    {
        $this->score_history = [];
        $this->player_ids = collect();
    }

    public function addToScoreHistory(string $icon, int $points, string $description, bool $is_hidden = false)
    {
        $this->score_history[] = [
            'icon' => $icon,
            'points' => $points,
            'description' => $description,
            'timestamp' => now(),
            'is_hidden' => $is_hidden,
        ];
    }

    public function score(bool $include_hidden = false): int
    {
        if ($include_hidden) {
            return collect($this->score_history)->sum('points');
        }

        return collect($this->score_history)
            ->filter(fn ($item) => ! $item['is_hidden'])
            ->sum('points');
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
