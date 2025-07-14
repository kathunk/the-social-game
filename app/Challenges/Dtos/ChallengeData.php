<?php

namespace App\Challenges\Dtos;

use App\States\ChallengeState;
use App\States\GameState;

abstract class ChallengeData
{
    abstract public static function fromGameAndChallenge(GameState $game, ChallengeState $challenge): static;
}
