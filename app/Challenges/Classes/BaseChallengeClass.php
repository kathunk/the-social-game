<?php

namespace App\Challenges\Classes;

use App\Models\Challenge;
use App\States\GameState;
use App\States\TeamState;
use App\States\PlayerState;
use App\States\ChallengeState;
use App\Challenges\ChallengeFormBuilder;

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

    public function onPlayerJoinedTeam(
        PlayerState $player_state,
        TeamState $team_state,
        GameState $game_state,
        ?TeamState $previous_team = null,
    ) {
        // Optional override
    }

    public function onChallengeEnded(
        GameState $game_state,
    ) {
        // Optional override
    }

    public function form(): ChallengeFormBuilder
    {
        return new ChallengeFormBuilder();
    }

    public function frontendComponent(): array
    {
        return [];
    }
}
