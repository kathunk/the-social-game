<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Team;
use Livewire\Component;
use Livewire\Attributes\Computed;

class TeamPage extends Component
{
    public Game $game;
    public Team $team;

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

    public function mount(string $snowflake, Team $team)
    {
        $this->game = Game::findOrFail($snowflake);
        $this->team = $team;
    }

    public function render()
    {
        return view('livewire.team-page');
    }
}
