<?php

namespace App\Livewire;

use Flux\Flux;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Computed;

class AdminDashboard extends Component
{
    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function newUsers()
    {
        return User::where('status', 'new')->orderBy('created_at', 'desc')->get();
    }

    public function mount()
    {
        if (! $this->user->is_admin) {
            return redirect()->route('dashboard');
        }
    }

    public function approveUser(string $user_id)
    {
        UserApproved::fire(
            user_id: (int) $user_id,
            admin_id: $this->user->id,
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
