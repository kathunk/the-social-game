<?php

namespace App\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;

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

    #[Computed]
    public function is_super_admin()
    {
        return $this->user->is_super_admin;
    }

    #[Computed]
    public function is_game_admin()
    {
        return $this->user->is_admin($this->game);
    }

    #[Computed]
    public function requires_admin_approval_to_join()
    {
        return $this->game->requires_admin_approval_to_join;
    }

    public function checkStatus()
    {
        unset($this->application);

        if ($this->application->status === 'accepted') {
            return redirect()->route('home');
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
