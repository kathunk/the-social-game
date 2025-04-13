<?php

namespace App\Livewire;

use App\Models\Team;
use Livewire\Component;
use Livewire\Attributes\Computed;

class Dashboard extends Component
{
    public string $selected_team_id;

    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function player()
    {
        return $this->user->currentPlayer;
    }

    #[Computed]
    public function game()
    {
        return $this->player->game;
    }

    #[Computed]
    public function teams()
    {
        return $this->game->teams->sortByDesc('score');
    }

    #[Computed]
    public function current_team()
    {
        return $this->player->team;
    }

    public function mount()
    {
        if (! $this->player) {
            return redirect()->route('pre-game-lobby');
        }
    }

    public function joinTeam()
    {
        $team = Team::find($this->selected_team_id);
        $this->player->joinTeam($team);
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
