<?php

namespace App\Support;

use App\Challenges\BaseChallengeClass;
use App\Challenges\Support\PeckingOrder\SupportsPeckingOrderBallots;
use App\Challenges\Support\Laracon2025\SupportsTeamSwaps;
use App\Modifiers\BaseModifierClass;
use Illuminate\Support\Collection;

/**
 * Per-game-mode element methods (e.g. ->farmMap(), ->elephantBoard())
 * are NOT defined here. They live on auto-discovered
 * FormElementProvider classes under app/Support/FormBuilderElements/ and
 * are resolved through __call — see FormElementRegistry. Adding a new game
 * mode's custom UI should never require editing this file.
 */
class FormBuilder
{
    protected array $elements = [];

    protected ?int $poll_interval = null;

    protected array $group = [];

    public function __construct(
        public ?BaseChallengeClass $challenge_class = null,
        public ?BaseModifierClass $modifier_class = null,
    ) {
        //
    }

    protected function getCurrentGroup(): ?array
    {
        return !empty($this->group)
            ? $this->group[count($this->group) - 1]
            : null;
    }

    protected function setCurrentGroup(array $group): void
    {
        if (empty($this->group)) {
            throw new \RuntimeException('Cannot set current group: stack is empty');
        }

        $this->group[count($this->group) - 1] = $group;
    }

    protected function addGroup(array $group): void
    {
        $this->group[] = $group;
    }

    protected function removeCurrentGroup()
    {
        return array_pop($this->group);
    }

    public function button(
        string $label,
        string $action,
        array $properties_to_validate = [],
        null|bool|array $has_confirmation = null,
    ): static {
        $target_class = $this->challenge_class ?? $this->modifier_class;

        // @todo ensure that the properties to validate are valid properties of the target class

        if (! $target_class) {
            throw new \InvalidArgumentException('Neither challenge_class nor modifier_class is set.');
        }

        if (! method_exists($target_class, $action)) {
            throw new \InvalidArgumentException("Method [{$action}] does not exist on [".get_class($target_class).'].');
        }

        $method = new \ReflectionMethod($target_class, $action);
        $params = $method->getParameters();

        if (count($params) !== 2) {
            throw new \InvalidArgumentException("Method [{$action}] on [".get_class($target_class).'] must have exactly two parameters: (Player $player, array $params).');
        }

        [$playerParam, $paramsParam] = $params;

        // Validate first param is type Player
        if (
            ! $playerParam->hasType() ||
            $playerParam->getType()->getName() !== \App\Models\Player::class
        ) {
            throw new \InvalidArgumentException("First parameter of method [{$action}] must be type-hinted as Player.");
        }

        // Validate second param is named 'params' and is an array
        if (
            $paramsParam->getName() !== 'params' ||
            ! $paramsParam->hasType() ||
            $paramsParam->getType()->getName() !== 'array'
        ) {
            throw new \InvalidArgumentException("Second parameter of method [{$action}] must be named 'params' and type-hinted as array.");
        }

        $button = [
            'type' => 'button',
            'label' => $label,
            'action' => $action,
            'properties_to_validate' => $properties_to_validate,
            'has_confirmation' => $has_confirmation,
        ];

        $this->addToElements($button);

        return $this;
    }

    public function buttonGroup(): static
    {
        $this->addGroup([
            'type' => 'button_group',
            'buttons' => [],
        ]);

        return $this;
    }

    public function endGroup(): static
    {
        $group = $this->getCurrentGroup();

        if (isset($group['type'])) {
            $this->removeCurrentGroup();
            $this->addToElements($group);
        } elseif ($group !== null) {
            throw new \RuntimeException('endGroup() failed for ' . print_r($group, true));
        }

        return $this;
    }

    public function input(
        string $property_name, // this will be the name of the property in livewire
        string $validation_rules,
        array $validation_messages,
        ?string $size = 'small',
        ?string $label = null,
        ?string $placeholder = null,
    ): static {
        $this->addToElements([
            'type' => 'input',
            'property_name' => $property_name,
            'label' => $label,
            'placeholder' => $placeholder,
            'validation_rules' => $validation_rules,
            'validation_messages' => $validation_messages,
            'size' => $size,
        ]);

        return $this;
    }

    public function select(
        string $label,
        array $options,
        string $property_name, // this will be the name of the property in livewire
        string $validation_rules,
        array $validation_messages,
        ?string $placeholder = null,
        ?bool $searchable = true,
    ): static {
        $this->addToElements([
            'type' => 'select',
            'label' => $label,
            'options' => $options,
            'property_name' => $property_name,
            'validation_rules' => $validation_rules,
            'validation_messages' => $validation_messages,
            'placeholder' => $placeholder,
            'searchable' => $searchable,
        ]);

        return $this;
    }

    public function radioGroup(
        string $label,
        array $options,
        string $property_name, // this will be the name of the property in livewire
        string $validation_rules,
        array $validation_messages,
    ): static {
        $this->addToElements([
            'type' => 'radio_group',
            'label' => $label,
            'options' => $options,
            // example:
            // 'options' => [
            //     [
            //         'label' => 'Strategist',
            //         'value' => 'strategist',
            //         'description' => 'Max capacity of 5 Actions',
            //         'disabled' => false,
            //     ],
            // ],
            'property_name' => $property_name,
            'validation_rules' => $validation_rules,
            'validation_messages' => $validation_messages,
        ]);

        return $this;
    }

    public function title(string $text): static
    {
        $this->addToElements([
            'type' => 'title',
            'text' => $text,
        ]);

        return $this;
    }

    public function subtitle(string $text): static
    {
        $this->addToElements([
            'type' => 'subtitle',
            'text' => $text,
        ]);

        return $this;
    }

    public function table(array $headers, array $rows): static
    {
        $this->addToElements([
            'type' => 'table',
            'headers' => $headers,
            'rows' => $rows,
        ]);

        return $this;
    }

    public function hideable()
    {
        $this->addGroup([
            'type' => 'hideable',
            'trigger' => null,
            'hidden' => null,
        ]);

        return $this;
    }

    public function trigger(
        bool $show_caret = true,
        string $more_text = 'Show More',
        string $less_text = 'Show Less'
    ) {
        $group = $this->getCurrentGroup();

        if (isset($group['type']) && $group['type'] === 'hideable') {
            $group['trigger'] = [
                'show_caret' => $show_caret,
                'more_text' => $more_text,
                'less_text' => $less_text,
            ];
            $this->setCurrentGroup($group);
        }

        return $this;
    }

    public function hidden()
    {
        $group = $this->getCurrentGroup();

        if (isset($group['type']) && $group['type'] === 'hideable') {
            $group['hidden'] = [];
            $this->setCurrentGroup($group);
        }

        return $this;
    }

    public function endHideable()
    {
        return $this->endGroup();
    }

    protected function addToElements(array $element): void
    {
        $group = $this->getCurrentGroup();

        if ($group === null) {
            // No active context, add to root elements
            $this->elements[] = $element;
            return;
        }

        if ($group['type'] === 'button_group' && $element['type'] === 'button') {
            $group['buttons'][] = $element;
            $this->setCurrentGroup($group);
        } elseif ($group['type'] === 'hideable' && isset($group['hidden'])) {
            $group['hidden'][] = $element;
            $this->setCurrentGroup($group);
        } else {
            throw new \RuntimeException('Attempt to add element to unknown group' . print_r($group, true));
        }
    }

    public function divider(): static
    {
        $this->addToElements([
            'type' => 'divider',
        ]);

        return $this;
    }

    public function image(string $url, ?string $alt = null): static
    {
        $this->addToElements([
            'type' => 'image',
            'url' => $url,
            'alt' => $alt,
        ]);

        return $this;
    }

    public function build(): array
    {
        if (!empty($this->group)) {
            $groupTypes = array_map(fn($ctx) => $ctx['type'], $this->group);
            throw new \RuntimeException('You must close all open contexts before build(). Open contexts: '.implode(', ', $groupTypes));
        }

        $result = [
            'type' => 'form',
            'elements' => $this->elements,
        ];

        if ($this->poll_interval !== null) {
            $result['poll_interval'] = $this->poll_interval;
        }

        return $result;
    }

    public function teamSwap(
        Collection $teams,
        ?string $label = 'Choose a team to swap to',
    ) {
        if (! $this->challenge_class instanceof SupportsTeamSwaps) {
            throw new \RuntimeException('Challenge class must implement SupportsTeamSwaps interface');
        }

        $this->select(
            label: $label,
            placeholder: 'Select a team...',
            options: $teams->mapWithKeys(fn ($team) => [$team->id => $team->name])->toArray(),
            property_name: 'team_id',
            validation_rules: 'required|exists:teams,id',
            validation_messages: [
                'required' => 'Team is required',
                'exists' => 'Team is invalid',
            ],
        );

        $this->buttonGroup();
        $this->button('Swap Team', 'swapTeams', ['team_id']);
        $this->endGroup();

        return $this;
    }

    public function peckingOrderBallot(
        ?Collection $upvote_targets,
        ?Collection $downvote_targets,
        ?string $upvote_label = 'Choose a player to upvote',
        ?string $downvote_label = 'Choose a player to downvote',
    ) {
        if (! $this->challenge_class instanceof SupportsPeckingOrderBallots) {
            throw new \RuntimeException('Challenge class must implement SupportsPeckingOrderBallots interface');
        }

        $this->select(
            label: $upvote_label,
            options: $upvote_targets->mapWithKeys(fn ($player) => [$player->id => $player->name])->toArray(),
            property_name: 'upvote_player_id',
            placeholder: 'Select a player...',
            validation_rules: 'required|in:'.implode(',', $upvote_targets->pluck('id')->toArray()),
            validation_messages: ['required' => 'Player is required', 'in' => 'Player is invalid'],
        );

        $this->select(
            label: $downvote_label,
            options: $downvote_targets->mapWithKeys(fn ($player) => [$player->id => $player->name])->toArray(),
            property_name: 'downvote_player_id',
            placeholder: 'Select a player...',
            validation_rules: 'required|in:'.implode(',', $downvote_targets->pluck('id')->toArray()),
            validation_messages: ['required' => 'Player is required', 'in' => 'Player is invalid'],
        );

        $this->buttonGroup();
        $this->button('Vote', 'vote', ['upvote_player_id', 'downvote_player_id']);
        $this->endGroup();

        return $this;
    }

    /**
     * Append a raw element array. This is the surface FormElementProvider
     * classes build on — elements need a unique snake_case 'type', which the
     * generic form blade resolves to a matching custom-form-elements blade.
     */
    public function addElement(array $element): static
    {
        $this->addToElements($element);

        return $this;
    }

    /**
     * Resolve per-game-mode element methods from the auto-discovered
     * registry. Named arguments pass straight through to the provider.
     */
    public function __call(string $method, array $arguments)
    {
        $provider = FormElementRegistry::resolve($method);

        if (! $provider) {
            throw new \BadMethodCallException(
                "Method [{$method}] does not exist on FormBuilder and no FormElementProvider under app/Support/FormBuilderElements/ defines it."
            );
        }

        $provider->{$method}($this, ...$arguments);

        return $this;
    }

    public function merge(FormBuilder $other): static
    {
        $this->elements = array_merge($this->elements, $other->elements);

        return $this;
    }

    public function when(bool $condition, callable $callback): static
    {
        if ($condition) {
            $callback($this);
        }

        return $this;
    }

    public function poll(int $interval): static
    {
        $this->poll_interval = $interval;

        return $this;
    }

    public static function section(): static
    {
        return new static;
    }
}
