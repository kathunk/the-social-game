<?php

namespace App\Modifiers\Classes;

use App\Models\User;
use App\Models\Player;
use App\Models\Modifier;
use App\States\GameState;
use App\States\TeamState;
use App\States\PlayerState;
use App\Support\FormBuilder;
use App\States\ModifierState;

abstract class BaseModifierClass
{
    const NAME = 'Base Modifier';

    const DESCRIPTION = 'Base Modifier description';

    const TYPE = 'team'; // team or individual

    const REQUIRES_PRE_GAME_CONFIGURATION = false;

    abstract public static function key(): string;

    public ?Player $player = null;

    public ?PlayerState $player_state = null;

    public function __construct(
        public ?Modifier $modifier = null,
        public ?ModifierState $modifier_state = null
    ) {}

    public static function fromModel(Modifier $modifier): static
    {
        return new static(modifier: $modifier);
    }

    public static function fromState(ModifierState $modifier_state): static
    {
        return new static(modifier_state: $modifier_state);
    }

    public static function fromKey(string $key): static
    {
        return new static;
    }

    public function dataArrayForState(): array
    {
        return [];
    }

    public function isInvalidForTemplate(
        array $challenges,
        array $modifiers,
        string $type,
        array $team_names
    ) {
        return false;
    }

    public function onSecretDiscovered(Player $player)
    {
        // Optional override
    }

    public function onRoundEnded()
    {
        // Optional override
    }

    public function onPlayerJoinedTeam(
        PlayerState $player_state,
        TeamState $team_state,
        GameState $game_state,
        ModifierState $modifier_state,
        ?TeamState $previous_team = null
    ) {
        // Optional override
    }

    public function onChallengeEnded(GameState $game_state)
    {
        // Optional override
    }

    public function form(): FormBuilder
    {
        return new FormBuilder(modifier_class: $this);
    }

    public function frontendComponent(Player $player): array
    {
        return [];
    }

    public function frontendComponentForDedicatedPage(Player $player): array
    {
        return [];
    }

    public function propertiesForLivewire(Player $player): array
    {
        $properties = [];

        $component = $this->frontendComponent($player);

        if (! isset($component['elements'])) {
            return [];
        }

        foreach ($component['elements'] as $element) {
            if (
                isset($element['property_name']) &&
                isset($element['type']) &&
                $element['type'] === 'select'
            ) {
                // null selects must be empty strings for LW
                $properties[$element['property_name']] = '';
            } elseif (isset($element['property_name'])) {
                $properties[$element['property_name']] = null;
            }
        }

        return $properties;
    }

    public function validationRulesForLivewire(Player $player): array
    {
        $component = $this->frontendComponent($player);

        if (! isset($component['elements'])) {
            return ['rules' => [], 'messages' => []];
        }

        return collect($component['elements'])
            ->filter(
                fn ($element) => isset(
                    $element['property_name'],
                    $element['validation_rules']
                )
            )
            ->reduce(
                function ($carry, $element) {
                    $property = "{$element['property_name']}";

                    $carry['rules'][$property] = $element['validation_rules'];

                    if (isset($element['validation_messages'])) {
                        $carry['messages'] = array_merge(
                            $carry['messages'] ?? [],
                            collect($element['validation_messages'])
                                ->mapWithKeys(
                                    fn ($message, $rule) => [
                                        "{$property}.{$rule}" => $message,
                                    ]
                                )
                                ->all()
                        );
                    }

                    return $carry;
                },
                ['rules' => [], 'messages' => []]
            );
    }
}
