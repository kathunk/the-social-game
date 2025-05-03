<?php

namespace App\Support;

use App\Challenges\Classes\BaseChallengeClass;
use App\Challenges\Support\Interfaces\SupportsTeamSwaps;
use App\Modifiers\Classes\BaseModifierClass;
use Illuminate\Support\Collection;

class FormBuilder
{
    protected array $elements = [];

    protected ?array $currentGroup = null;

    public function __construct(
        public ?BaseChallengeClass $challenge_class = null,
        public ?BaseModifierClass $modifier_class = null,
    ) {
        //
    }

    public function button(string $label, string $action, array $properties_to_validate = []): static
    {
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
        ];

        if ($this->currentGroup !== null) {
            $this->currentGroup['buttons'][] = $button;
        } else {
            $this->elements[] = $button;
        }

        return $this;
    }

    public function buttonGroup(): static
    {
        $this->currentGroup = [
            'type' => 'button_group',
            'buttons' => [],
        ];

        return $this;
    }

    public function endGroup(): static
    {
        if ($this->currentGroup !== null) {
            $this->elements[] = $this->currentGroup;
            $this->currentGroup = null;
        }

        return $this;
    }

    public function input(
        string $property_name, // this will be the name of the property in livewire
        string $validation_rules,
        array $validation_messages,
        ?string $label = null,
        ?string $description = null,
        ?string $placeholder = null,
    ): static {
        $this->elements[] = [
            'type' => 'input',
            'property_name' => $property_name,
            'label' => $label,
            'description' => $description,
            'placeholder' => $placeholder,
            'validation_rules' => $validation_rules,
            'validation_messages' => $validation_messages,
        ];

        return $this;
    }

    public function select(
        string $label,
        array $options,
        string $property_name, // this will be the name of the property in livewire
        string $validation_rules,
        array $validation_messages,
        ?string $description = null,
        ?string $placeholder = null,
    ): static {
        $this->elements[] = [
            'type' => 'select',
            'label' => $label,
            'description' => $description,
            'options' => $options,
            'property_name' => $property_name,
            'validation_rules' => $validation_rules,
            'validation_messages' => $validation_messages,
            'placeholder' => $placeholder,
        ];

        return $this;
    }

    public function title(string $text): static
    {
        $this->elements[] = [
            'type' => 'title',
            'text' => $text,
        ];

        return $this;
    }

    public function subtitle(string $text): static
    {
        $this->elements[] = [
            'type' => 'subtitle',
            'text' => $text,
        ];

        return $this;
    }

    public function message(string $text): static
    {
        $this->elements[] = [
            'type' => 'message',
            'text' => $text,
        ];

        return $this;
    }

    public function table(array $rows): static
    {
        $this->elements[] = [
            'type' => 'table',
            'rows' => $rows,
        ];

        return $this;
    }

    public function divider(): static
    {
        $this->elements[] = [
            'type' => 'divider',
        ];

        return $this;
    }

    public function image(string $url, ?string $alt = null): static
    {
        $this->elements[] = [
            'type' => 'image',
            'url' => $url,
            'alt' => $alt,
        ];

        return $this;
    }

    public function build(): array
    {
        if ($this->currentGroup !== null) {
            throw new \RuntimeException('You must call endGroup() before build()');
        }

        return [
            'type' => 'form',
            'elements' => $this->elements,
        ];
    }

    public function teamSwap(
        Collection $teams,
        ?string $label = 'Choose a team to swap to',
        ?string $description = 'You can swap teams once during this challenge.',
    ) {
        if (! $this->challenge_class instanceof SupportsTeamSwaps) {
            throw new \RuntimeException('Challenge class must implement SupportsTeamSwaps interface');
        }

        $this->select(
            label: $label,
            description: $description,
            options: $teams->mapWithKeys(fn ($team) => [$team->id => $team->name])->toArray(),
            property_name: 'team_id',
            validation_rules: 'required',
            validation_messages: ['required' => 'Team is required'],
        );

        $this->buttonGroup();
        $this->button('Swap Team', 'swapTeams', ['team_id']);
        $this->endGroup();

        return $this;
    }
}
