<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Team;
use Livewire\Component;
use Livewire\Attributes\Computed;

class TeamPage extends Component
{
    public Team $team;
    public Game $game;

    #[Computed]
    public function players()
    {
        return $this->team->players;
    }

    #[Computed]
    public function scoreHistoryEntries(): array
    {
        return array_reverse($this->team->state()->score_history);
    }

    public function mount(Game $game, Team $team)
    {
        $this->game = $game;
        $this->team = $team;
    }

    public function render()
    {
        return view('livewire.team-page');
    }
}
