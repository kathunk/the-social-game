<?php

namespace App\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;

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
        $statusOrder = [
            'active' => 1,
            'upcoming' => 2,
            'ended' => 3,
            'canceled' => 4,
        ];

        return $this->user->games()
            ->orderByRaw("CASE 
                WHEN games.status = 'active' THEN 1
                WHEN games.status = 'upcoming' THEN 2
                WHEN games.status = 'ended' THEN 3
                WHEN games.status = 'canceled' THEN 4
                ELSE 5
            END")
            ->orderBy('starts_at', 'desc')
            ->get();
    }

    public function render()
    {
        return view('livewire.home');
    }
}
