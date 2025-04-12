<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Computed;

class PreGameLobby extends Component
{
    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    public function mount()
    {
        // dd($this->user->is_admin);
    }

    public function render()
    {
        return view('livewire.pre-game-lobby');
    }
}
