<?php

namespace App\Livewire;

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
        return $this->user->games;
    }

    public function render()
    {
        return view('livewire.home');
    }
}
