<?php

namespace App\Livewire;

use Livewire\Component;
use App\Support\HtmlTransformer;
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

    #[Computed]
    public function description()
    {
        return (new HtmlTransformer($this->game->gameTemplate->pre_game_lobby_message))->fluxify();
    }

    public function checkStatus()
    {
        unset($this->application);

        if ($this->application->status === 'accepted' && $this->game->status === 'active') {
            return redirect()->route('game-dashboard', ['game' => $this->game]);
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
