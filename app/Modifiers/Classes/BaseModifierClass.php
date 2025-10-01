<?php

namespace App\Modifiers\Classes;

use App\Models\Player;
use App\Models\Modifier;
use App\States\GameState;
use App\States\TeamState;
use App\States\PlayerState;
use App\Support\FormBuilder;
use App\States\ModifierState;
use App\States\ChallengeState;
use App\Support\FrontendComponentProcessor;

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

    public function onGameStarted(
        GameState $game_state,
        ModifierState $modifier_state,
    ) {
        // Optional override
    }

    public function onSecretDiscovered(Player $player)
    {
        // Optional override
    }

    public function onChallengeStarted(
        GameState $game_state,
        ChallengeState $challenge_state,
        ModifierState $modifier_state,
    )
    {
        // Optional override
    }

    public function onUserAdmittedToGame(
        PlayerState $player_state,
        GameState $game_state,
        ModifierState $modifier_state,
    ) {
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

    public function propertiesForLivewire(Player $player, ?bool $for_dedicated_page = false): array
    {
        return FrontendComponentProcessor::propertiesForLivewire(
            $for_dedicated_page 
                ? $this->frontendComponentForDedicatedPage($player) 
                : $this->frontendComponent($player),
            useEmptyStringForSelects: true
        );
    }

    public function validationRulesForLivewire(Player $player): array
    {
        return FrontendComponentProcessor::validationRulesForLivewire(
            $this->frontendComponent($player)
        );
    }
}
