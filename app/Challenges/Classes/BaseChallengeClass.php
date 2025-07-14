<?php

namespace App\Challenges\Classes;

use App\Challenges\Dtos\ChallengeData;
use App\Challenges\Support\Interfaces\SupportsTeamSwaps;
use App\Models\Challenge;
use App\Models\Player;
use App\States\ChallengeState;
use App\States\GameState;
use App\States\PlayerState;
use App\States\TeamState;
use App\Support\FormBuilder;
use App\Support\FrontendComponentProcessor;

abstract class BaseChallengeClass
{
    const NAME = 'Base Challenge';

    const DESCRIPTION = 'Base Challenge description';

    const TYPE = 'team'; // team or individual

    const HIDE_SCOREBOARD = false;

    abstract public static function key(): string;

    public ?Player $player = null;

    public ?PlayerState $player_state = null;

    public ?string $challenge_data_class = null;

    public function __construct(
        public ?Challenge $challenge = null,
        public ?ChallengeState $challenge_state = null
    ) {}

    public static function fromModel(Challenge $challenge): static
    {
        return new static(challenge: $challenge);
    }

    public static function fromState(ChallengeState $challenge): static
    {
        return new static(challenge_state: $challenge);
    }

    public static function fromKey(string $key): static
    {
        return new static;
    }

    public function isInvalidForTemplate(
        array $challenge_keys,
        array $modifier_keys,
        string $type,
        array $team_names
    ) {
        return false;
    }

    public function dataArrayForState(): array
    {
        return [];
    }

    public function playerCanSwapTeams(
        ?Player $player = null,
        ?PlayerState $player_state = null
    ): bool {
        return false;
    }

    public function prepareChallengeData(GameState $game, ChallengeState $challenge): ?ChallengeData
    {
        // Optional override

        return null;
    }

    public function onChallengeStarted(GameState $game_state)
    {
        // Optional override
    }

    public function onChallengeEnded(GameState $game_state)
    {
        // Optional override
    }

    public function onPlayerJoinedTeam(
        PlayerState $player_state,
        TeamState $team_state,
        GameState $game_state,
        ?TeamState $previous_team = null
    ) {
        // Optional override
    }

    public function supportsTeamSwaps(): bool
    {
        return $this instanceof SupportsTeamSwaps;
    }

    public function form(): FormBuilder
    {
        return new FormBuilder(challenge_class: $this);
    }

    public function frontendComponent(Player $player): array
    {
        return [];
    }

    public function propertiesForLivewire(Player $player): array
    {
        return FrontendComponentProcessor::propertiesForLivewire(
            $this->frontendComponent($player)
        );
    }

    public function validationRulesForLivewire(Player $player): array
    {
        return FrontendComponentProcessor::validationRulesForLivewire(
            $this->frontendComponent($player)
        );
    }
}
