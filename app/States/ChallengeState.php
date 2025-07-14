<?php

namespace App\States;

use App\Challenges\ChallengeRegistry;
use App\Challenges\Classes\BaseChallengeClass;
use App\Challenges\Dtos\ChallengeData;
use Illuminate\Support\Carbon;
use Thunk\Verbs\State;

class ChallengeState extends State
{
    public int $game_id;

    public string $class_key;

    public string $status;

    public Carbon $starts_at;

    public Carbon $ends_at;

    public bool $allows_turncoat;

    public array $challenge_data;

    public ChallengeData $challenge_data_dto;

    public function game(): GameState
    {
        return GameState::load($this->game_id);
    }

    public function handler(): BaseChallengeClass
    {
        return ChallengeRegistry::retrieveFromState($this->class_key, $this);
    }
}
