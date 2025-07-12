<?php

namespace App\Livewire;

use App\Events\SecretCodesAddedToModifier;
use App\Models\Game;
use App\Modifiers\Classes\TeamSecretCodes;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ModifierConfigurationPage extends Component
{
    public Game $game;

    public string $secretCodes = '';

    #[Computed]
    public function modifierConfigurations()
    {
        return $this->game->modifierConfigurations;
    }

    // @todo we probably don't want to make this so rigid and specific.
    // but I don't know what the abstraction is yet, so I'm leaving this as is for now.
    #[Computed]
    public function secretCodeConfiguration()
    {
        return $this->modifierConfigurations
            ->filter(fn ($config) => $config->modifier_key === TeamSecretCodes::key())
            ->first();
    }

    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function isAdmin()
    {
        return $this->user->isGameAdmin($this->game);
    }

    public function mount(Game $game)
    {
        $this->game = $game;

        if (! $this->isAdmin || $this->modifierConfigurations->count() === 0) {
            return redirect()->route('game-dashboard', ['game' => $this->game]);
        }

        $codes = $this->secretCodeConfiguration->modifier_data['unused_codes'] ?? [];
        $this->secretCodes = implode(', ', $codes);
    }

    public function saveSecretCodes()
    {
        $codes = array_map('trim', explode(',', $this->secretCodes));
        $codes = array_filter($codes);

        $validator = validator(['codes' => $codes], [
            'codes' => 'required|array|min:1',
            'codes.*' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            $this->addError('secretCodes', 'Invalid codes format');

            return;
        }

        SecretCodesAddedToModifier::fire(
            game_id: $this->game->id,
            modifier_configuration_id: $this->secretCodeConfiguration->id,
            codes: $codes
        );
    }

    public function render()
    {
        return view('livewire.modifier-configuration-page');
    }
}
