<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesClassActions;
use App\Models\Game;
use App\Models\Modifier;
use App\Support\FrontendComponentProcessor;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SecretsPage extends Component
{
    use HandlesClassActions;

    public Game $game;

    public Modifier $modifier;

    public array $frontend_component;

    public array $round_properties;

    public array $validation_rules;

    public array $validation_messages;

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
        $this->round_properties = [
            $this->modifier->class_key => $this->modifier->modifier_data,
        ];
        $this->frontend_component = $this->modifier
            ->handler()
            ->frontendComponentForDedicatedPage($this->player);
        $rules = FrontendComponentProcessor::validationRulesForLivewire(
            $this->frontend_component
        );
        $this->validation_rules = [$this->modifier->class_key => $rules];
    }

    public function rules()
    {
        $rules = [
            'round_properties' => 'array',
            'round_properties.*' => 'nullable',
        ];

        foreach ($this->validation_rules as $class_key => $validation) {
            if (! empty($validation['rules'])) {
                $transformed_rules = [];
                foreach ($validation['rules'] as $key => $rule) {
                    $transformed_rules[
                        "round_properties.$class_key.$key"
                    ] = $rule;
                }
                $rules = array_merge($rules, $transformed_rules);
            }
        }

        if (isset($this->filtered_rules)) {
            $rules = array_merge($rules, $this->filtered_rules);
        }

        return $rules;
    }

    public function render()
    {
        return view('livewire.secrets-page');
    }
}
