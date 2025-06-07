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

    public bool $is_public;

    public bool $is_archived = false;

    public function durationOfAllChallengesInMinutes(): int
    {
        return collect($this->challenges)->sum(fn ($c) => $c['duration'] ?? 0);
    }
}
