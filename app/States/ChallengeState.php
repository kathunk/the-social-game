<?php

namespace App\States;

use App\Challenges\ChallengeRegistry;
use App\Challenges\BaseChallengeClass;
use Illuminate\Support\Carbon;
use Thunk\Verbs\State;

class ChallengeState extends State
{
    public int $game_id;

    public string $class_key;

    public string $status;

    public ?Carbon $starts_at = null;

    public ?Carbon $ends_at = null;

    public bool $allows_turncoat;

    public array $challenge_data;

    public ?int $round_number = null;

    public function game(): GameState
    {
        return GameState::load($this->game_id);
    }

    public function handler(): BaseChallengeClass
    {
        return ChallengeRegistry::retrieveFromState($this->class_key, $this);
    }
}
