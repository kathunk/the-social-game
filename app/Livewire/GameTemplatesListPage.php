<?php

namespace App\Livewire;

use App\Models\GameTemplate;
use Livewire\Attributes\Computed;
use Livewire\Component;

class GameTemplatesListPage extends Component
{
    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function gameTemplates()
    {
        return GameTemplate::all();
    }

    public function mount()
    {
        if (! $this->user->is_super_admin) {
            return redirect()->route('dashboard');
        }
    }

    public function render()
    {
        return view('livewire.game-templates-list-page');
    }
}
