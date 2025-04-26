<?php

namespace App\States;

use Thunk\Verbs\State;

class GameTemplateState extends State
{
    public string $name;

    public string $description;

    public string $type;

    public int $min_players;

    public ?int $max_players = null;

    public bool $is_public = false;

    public array $team_names;

    public array $challenges;

    public function durationOfAllChallengesInMinutes(): int
    {
        return collect($this->challenges)->sum();
    }
}
