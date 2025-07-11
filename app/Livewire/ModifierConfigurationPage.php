<?php

namespace App\Livewire;

use App\Models\Game;
use Livewire\Component;
use Livewire\Attributes\Computed;

class ModifierConfigurationPage extends Component
{
    public Game $game;

    #[Computed]
    public function modifierConfigurations()
    {
        return $this->game->modifierConfigurations;
    }

    public function mount(Game $game)
    {
        $this->game = $game;
    }

    public function render()
    {
        return view('livewire.modifier-configuration-page');
    }
}
