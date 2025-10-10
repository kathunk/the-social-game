<?php

namespace App\States;

use Illuminate\Support\Carbon;
use Thunk\Verbs\State;

class PlayerState extends State
{
    public string $name;

    public string $status;

    public int $user_id;

    public int $game_id;

    public ?int $team_id = null;

    public Carbon $last_switched_team_at;

    public array $score_history = [];

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
}
