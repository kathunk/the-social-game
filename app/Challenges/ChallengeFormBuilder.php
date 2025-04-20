<?php

namespace App\Challenges;

use App\Challenges\Classes\BaseChallengeClass;

class ChallengeFormBuilder
{
    protected array $elements = [];

    protected ?array $currentGroup = null;

    public function __construct(
        public BaseChallengeClass $challenge_class
    ) {
        //
    }

    public function button(string $label, string $action): static
    {
        if (! method_exists($this->challenge_class, $action)) {
            throw new \InvalidArgumentException("Method [{$action}] does not exist on [".get_class($this->challenge_class).'].');
        }

        $button = [
            'type' => 'button',
            'label' => $label,
            'action' => $action,
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
        string $label,
        string $property_name, // this will be the name of the property in livewire
        string $validation_rules,
        array $validation_messages,
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
}
