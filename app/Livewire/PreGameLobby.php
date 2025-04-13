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

    #[Computed]
    public function game()
    {
        return $this->user->currentGame;
    }

    #[Computed]
    public function application()
    {
        return $this->user->gameApplications->where('game_id', $this->game->id)->first();
    }

    public function checkStatus()
    {
        unset($this->application);

        if ($this->application->status === 'accepted') {
            return redirect()->route('dashboard');
        }
    }

    public function mount()
    {
        $this->checkStatus();
    }

    public function render()
    {
        return view('livewire.pre-game-lobby');
    }
}
