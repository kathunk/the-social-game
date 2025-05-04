<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;

class Theme extends Component
{
    #[Layout('layouts.guest')]
    public function render()
    {
        return view('livewire.theme');
    }
}
