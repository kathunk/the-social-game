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
    public function hasBeenRejected()
    {
        return $this->user->gameApplications()->where('game_id', $this->game->id)->where('status', 'rejected')->exists();
    }

    public function render()
    {
        return view('livewire.pre-game-lobby');
    }
}
