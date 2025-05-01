<?php

namespace App\Livewire;

use App\Models\Game;
use Livewire\Component;
use Livewire\Attributes\Computed;

class Home extends Component
{
    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function games()
    {
        return $this->user->games()->orderBy('starts_at', 'desc')->get();
    }

    public function mount()
    {
        // dd(Game::first()->players->map(fn ($p) => $p->user_id), $this->user->id);
    }

    public function render()
    {
        return view('livewire.home');
    }
}
