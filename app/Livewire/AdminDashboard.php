<?php

namespace App\Livewire;

use Flux\Flux;
use App\Models\Game;
use Livewire\Component;
use App\Models\GameApplication;
use Livewire\Attributes\Computed;
use App\Events\UserAdmittedToGame;
use App\Events\UserRejectedFromGame;

class AdminDashboard extends Component
{
    public string $selected_application_id = '';

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
    public function newApplications()
    {
        return GameApplication::with('user')
            ->where([
                'game_id' => $this->game->id,
                'status' => 'pending',
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function acceptedUserNames()
    {
        return $this->game->players->map(fn ($player) => $player->user->name);
    }

    public function mount()
    {
        if (! $this->admin->is_admin) {
            return redirect()->route('dashboard');
        }
    }

    public function approveUser()
    {
        $application = $this->newApplications->firstWhere('id', (int) $this->selected_application_id);

        UserAdmittedToGame::fire(
            user_id: (int) $application->user_id,
            admin_id: $this->admin->id,
            game_id: $this->game->id,
            application_id: $application->id,
        );

        $this->selected_application_id = '';
        unset($this->newApplications);

        Flux::toast(variant: 'success', heading: 'User approved', text: 'The user has been approved to join the game.');
    }

    public function rejectUser()
    {
        $application = $this->newApplications->firstWhere('id', (int) $this->selected_application_id);

        UserRejectedFromGame::fire(
            user_id: (int) $application->user_id,
            admin_id: $this->admin->id,
            game_id: $this->game->id,
            application_id: $application->id,
        );

        $this->selected_application_id = '';
        unset($this->newApplications);

        Flux::toast(variant: 'success', heading: 'User rejected', text: 'The user has been rejected from the game.');
    }

    public function render()
    {
        return view('livewire.admin-dashboard');
    }
}
