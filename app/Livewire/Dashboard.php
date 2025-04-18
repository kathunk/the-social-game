<?php

namespace App\Livewire;

use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Thunk\Verbs\Facades\Verbs;

class Dashboard extends Component
{
    public string $selected_team_id;

    public int $quit_points;

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
        $this->validate([
            'selected_team_id' => 'required|exists:teams,id',
        ]);

        // @todo freaky ass bug where joinTeam fails when you choose the first team in the select

        $team = Team::find($this->selected_team_id);
        $this->player->joinTeam($team);
        $this->selected_team_id = '';

        Verbs::commit();

        redirect()->route('dashboard');
    }

    public function resign()
    {
        $this->player->resign($this->quit_points);

        Verbs::commit();

        redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
