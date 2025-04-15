<?php

namespace App\Challenges\Classes;

use App\Models\Challenge;
use App\States\ChallengeState;

abstract class BaseChallengeClass
{
    abstract public static function key(): string;

    const NAME = 'Challenge';

    const DESCRIPTION = 'Challenge Description';

    public function __construct(
        public ?Challenge $challenge = null,
        public ?ChallengeState $state = null
    ) {}

    public static function fromModel(Challenge $challenge): static
    {
        return new static(challenge: $challenge);
    }

    public static function fromState(ChallengeState $state): static
    {
        return new static(state: $state);
    }

    public function onRoundEnded()
    {
        // Optional override
    }
}
