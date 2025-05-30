<?php

namespace App\States;

use Thunk\Verbs\State;

class GameTemplateState extends State
{
    public string $name;

    public string $description;

    public string $type;

    public ?int $min_players = null;

    public ?int $max_players = null;

    public bool $is_public = false;

    public array $team_names;

    public array $challenges;

    public array $modifiers;

    public bool $players_can_join_late = false;

    public string $pre_game_lobby_message;

    public bool $is_archived = false;

    public string $scoreboard_type;

    public function durationOfAllChallengesInMinutes(): int
    {
        return collect($this->challenges)->sum(fn ($c) => $c['duration'] ?? 0);
    }
}
