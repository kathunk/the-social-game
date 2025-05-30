<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Modifier;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SecretsPage extends Component
{
    public Game $game;

    public Modifier $modifier;

    #[Computed]
    public function player()
    {
        return $this->game->players->firstWhere('user_id', $this->user->id);
    }

    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    public function mount(Game $game, Modifier $modifier)
    {
        $this->game = $game;
        $this->modifier = $modifier;

        $this->modifier->handler()->onSecretDiscovered($this->player);
    }

    // @todo maybe extract this into another class so it doesn't repeat
    public function callClassAction(string $action, string $type, string $class_key)
    {
        $params = $this->round_properties[$class_key];
        $params = ['round_properties' => $params];

        $handler = match ($type) {
            'challenge' => $this->challenge_handler,
            'modifier' => $this->modifiers->firstWhere('class_key', $class_key)->handler(),
        };

        $component = $handler->frontendComponent($this->player);

        $all_elements = collect($component['elements'])->flatMap(function ($el) {
            return [
                $el,
                ...collect($el['elements'] ?? [])->all(),
                ...collect($el['buttons'] ?? [])->all(),
            ];
        });

        $button = $all_elements->firstWhere(fn ($el) => ($el['type'] ?? null) === 'button' && ($el['action'] ?? null) === $action
        );

        $fields = $button['properties_to_validate'] ?? [];

        if (! empty($fields)) {
            $validation = $this->validation_rules[$class_key] ?? [];

            $filtered_rules = [];
            $filtered_messages = [];
            foreach ($fields as $field) {
                if (isset($validation['rules'][$field])) {
                    $filtered_rules["round_properties.$class_key.$field"] = $validation['rules'][$field];
                }
                if (isset($validation['messages'])) {
                    foreach ($validation['messages'] as $msg_key => $msg_val) {
                        if (str_starts_with($msg_key, "$field.")) {
                            $filtered_messages["round_properties.$class_key.$msg_key"] = $msg_val;
                        }
                    }
                }
            }

            $this->validate($filtered_rules, $filtered_messages);
        }

        $response = $handler->{$action}($this->player, $params['round_properties']);

        Verbs::commit();

        if ($type === 'challenge') {
            $this->challenge_component = $this->game->currentChallenge?->fresh()
                ->handler()->frontendComponent($this->player);
        }

        if ($type === 'modifier') {
            $this->modifiers = $this->game->fresh()->modifiers;
        }

        return $response instanceof \Illuminate\Http\RedirectResponse
            || $response instanceof \Livewire\Features\SupportRedirects\Redirector
            ? $response
            : null;
    }

    public function render()
    {
        return view('livewire.secrets-page');
    }
}
