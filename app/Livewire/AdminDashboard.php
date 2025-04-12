<?php

namespace App\Livewire;

use Flux\Flux;
use App\Models\Game;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Events\UserAdmittedToGame;

class AdminDashboard extends Component
{
    #[Computed]
    public function admin()
    {
        return auth()->user();
    }

    #[Computed]
    public function game(): Game
    {
        return $this->admin->currentGame;
    }

    #[Computed]
    public function newUsers()
    {
        return User::where('status', 'pending')->orderBy('created_at', 'desc')->get();
    }

    public function mount()
    {
        if (! $this->admin->is_admin) {
            return redirect()->route('dashboard');
        }
    }

    public function approveUser(string $user_id)
    {
        UserAdmittedToGame::fire(
            user_id: (int) $user_id,
            admin_id: $this->admin->id,
            game_id: $this->game->id,
        );

        $this->unset('newUsers');

        Flux::toast(variant: 'success', title: 'User approved');
    }

    public function userRejected(string $user_id)
    {
        UserRejected::fire(
            user_id: (int) $user_id,
            admin_id: $this->user->id,
        );

        $this->unset('newUsers');

        Flux::toast(variant: 'success', title: 'User rejected');
    }

    public function render()
    {
        return view('livewire.admin-dashboard');
    }
}
