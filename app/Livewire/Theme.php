<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;

class Theme extends Component
{
    public string $theme = 'laravel';

    #[Computed]
    public function themes()
    {
        return [
            'elixir',
            'go',
            'javascript',
            'laravel',
            'node',
            'php',
            'python',
            'react',
            'ruby',
            'vue',
        ];
    }

    #[Computed]
    public function forceLight(): string
    {
        return in_array($this->theme, ['php', 'ruby'])
            ? 'light'
            : '';
    }

    #[Layout('layouts.theme')]
    public function render()
    {
        return view('livewire.theme');
    }
}
