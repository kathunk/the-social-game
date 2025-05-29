<?php

namespace App\Challenges\Classes;

use App\Models\Player;
use App\Models\Challenge;
use App\States\GameState;
use App\States\TeamState;
use App\States\PlayerState;
use App\Support\FormBuilder;
use App\States\ChallengeState;
use Illuminate\Support\Collection;
use App\Challenges\Support\Interfaces\SupportsTeamSwaps;

abstract class BaseChallengeClass
{
    const NAME = 'Base Challenge';

    const DESCRIPTION = 'Base Challenge description';

    const TYPE = 'team'; // team or individual

    const HIDE_SCOREBOARD = false;

    abstract public static function key(): string;

    public ?Player $player = null;

    public ?PlayerState $player_state = null;

    public function __construct(
        public ?Challenge $challenge = null,
        public ?ChallengeState $challenge_state = null,
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

    public function isInvalidForTemplate(array $challenge_keys, array $modifier_keys, string $type, string $scoreboard_type)
    {
        return false;
    }

    public function dataArrayForState(): array
    {
        return [];
    }

    public function playerCanSwapTeams(?Player $player = null, ?PlayerState $player_state = null): bool
    {
        return false;
    }

    public function onChallengeStarted(
        GameState $game_state,
    ) {
        // Optional override
    }

    public function onChallengeEnded(
        GameState $game_state,
    ) {
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
        $properties = [];

        foreach ($this->frontendComponent($player)['elements'] as $element) {
            if (isset($element['property_name'])) {
                $properties[$element['property_name']] = null;
            }
        }

        return $properties;
    }

    public function validationRulesForLivewire(Player $player): array
    {
        return collect($this->frontendComponent($player)['elements'])
            ->filter(fn ($element) => isset($element['property_name'], $element['validation_rules']))
            ->reduce(function ($carry, $element) {
                // Remove the round_properties namespace from the property name
                $property = $element['property_name'];

                $carry['rules'][$property] = $element['validation_rules'];

                if (isset($element['validation_messages'])) {
                    $carry['messages'] = array_merge(
                        $carry['messages'] ?? [],
                        collect($element['validation_messages'])
                            ->mapWithKeys(fn ($message, $rule) => ["{$property}.{$rule}" => $message])
                            ->all()
                    );
                }

                return $carry;
            }, ['rules' => [], 'messages' => []]);
    }
}
