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
        return new ChallengeFormBuilder($this);
    }

    public function frontendComponent(): array
    {
        return [];
    }

    public function propertiesForLivewire(): array
    {
        $properties = [];

        foreach($this->frontendComponent()['elements'] as $element) {
            if(isset($element['property_name'])) {
                $properties[$element['property_name']] = null;
            }
        }

        return $properties;
    }

    public function validationRulesForLivewire(): array
    {
        return collect($this->frontendComponent()['elements'])
            ->filter(fn ($element) => isset($element['property_name'], $element['validation_rules']))
            ->reduce(function ($carry, $element) {
                $property = "challenge_properties.{$element['property_name']}";
                
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
