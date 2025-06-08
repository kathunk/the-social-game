<?php

namespace App\States;

use Thunk\Verbs\State;

class GameTemplateState extends State
{
    public string $name;

    public string $type;

    public array $team_names;

    public array $challenges;

    public array $modifiers;

    public string $scoreboard_type;

    public ?int $min_players = null;

    public ?int $max_players = null;

    public bool $players_can_join_late;

    public bool $is_public;

    public int $game_mode_id;

    public bool $is_archived = false;

    public function durationOfAllChallengesInMinutes(): int
    {
        return collect($this->challenges)->sum(fn ($c) => $c['duration'] ?? 0);
    }

    public function gameMode()
    {
        return GameModeState::load($this->game_mode_id);
    }
}
