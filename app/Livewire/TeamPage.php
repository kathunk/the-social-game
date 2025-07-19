<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamPage extends Component
{
    public Team $team;

    public Game $game;

    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function players()
    {
        return $this->team->players->sortBy('name');
    }

    #[Computed]
    public function game()
    {
        return $this->team->game;
    }

    #[Computed]
    public function scoreHistoryEntries()
    {
        return collect($this->team->state()->score_history)
            ->filter(fn ($entry) => $this->showHiddenPoints || ! $entry['is_hidden'])
            ->values()
            ->reverse();
    }

    #[Computed]
    public function showHiddenPoints()
    {
        $player_is_on_team = $this->players->pluck('user_id')->contains($this->user->id);
        $game_is_over = $this->game->status === 'ended';

        return $player_is_on_team || $game_is_over;
    }

    public function mount(Team $team)
    {
        $this->team = $team;
        // dd($this->team->score, $this->team->hidden_score);
    }

    public function render()
    {
        return view('livewire.team-page');
    }
}
