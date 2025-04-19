<?php

namespace App\Challenges;

class ChallengeFormBuilder
{
    protected array $elements = [];
    protected ?array $currentGroup = null;

    public function button(string $label): static
    {
        $button = [
            'type' => 'button',
            'label' => $label,
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

    public function input(string $name): static
    {
        $this->elements[] = [
            'type' => 'input',
            'name' => $name,
        ];

        return $this;
    }

    public function select(string $name): static
    {
        $this->elements[] = [
            'type' => 'select',
            'name' => $name,
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
            throw new \RuntimeException("You must call endGroup() before build()");
        }

        return [
            'type' => 'form',
            'elements' => $this->elements,
        ];
    }

    public function action(string $method): static
    {
        return $this->updateLast(fn (&$el) => $el['action'] = $method);
    }

    public function placeholder(string $text): static
    {
        return $this->updateLast(fn (&$el) => $el['placeholder'] = $text);
    }

    public function validation(string $rule): static
    {
        return $this->updateLast(fn (&$el) => $el['validation'] = $rule);
    }

    public function options(array $choices): static
    {
        return $this->updateLast(fn (&$el) => $el['options'] = $choices);
    }

    protected function updateLast(callable $callback): static
    {
        $index = count($this->elements) - 1;

        if ($index >= 0) {
            $callback($this->elements[$index]);
        }

        return $this;
    }
}