<?php

namespace App\Modifiers;

use App\Models\Game;
use App\Models\Modifier;
use App\Models\Player;
use App\States\ChallengeState;
use App\States\GameState;
use App\States\ModifierState;
use App\States\PlayerState;
use App\States\TeamState;
use App\Support\FormBuilder;
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

    public function dataArrayForState(?Game $game = null): array
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
    ) {
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

    /**
     * Third render surface (alongside frontendComponent and
     * frontendComponentForDedicatedPage): what this modifier shows on the
     * dashboard after the game has ended. This is the sanctioned home for
     * end-of-game UI (rematch buttons, recaps) — games end when their last
     * real challenge ends, and post-game concerns belong to modifiers, not
     * to buffer challenges.
     */
    public function postGameComponent(Player $player): array
    {
        return [];
    }

    public function propertiesForLivewire(
        Player $player,
        ?bool $for_dedicated_page = false,
        ?bool $for_post_game = false,
    ): array {
        $component = match (true) {
            (bool) $for_post_game => $this->postGameComponent($player),
            (bool) $for_dedicated_page => $this->frontendComponentForDedicatedPage($player),
            default => $this->frontendComponent($player),
        };

        return FrontendComponentProcessor::propertiesForLivewire(
            $component,
            useEmptyStringForSelects: true
        );
    }

    public function validationRulesForLivewire(Player $player, ?bool $for_post_game = false): array
    {
        return FrontendComponentProcessor::validationRulesForLivewire(
            $for_post_game
                ? $this->postGameComponent($player)
                : $this->frontendComponent($player)
        );
    }
}
