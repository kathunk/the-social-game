<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Player;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PlayerPage extends Component
{
    public Game $game;

    public Player $player;

    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function playerState()
    {
        return $this->player->state();
    }

    #[Computed]
    public function showHiddenPoints()
    {
        $player_is_player = $this->user()->id === $this->player->user_id;
        $game_is_over = $this->game->status === 'ended';

        return $player_is_player || $game_is_over;
    }

    #[Computed]
    public function scoreHistoryEntries()
    {
        return collect($this->playerState->score_history)
            ->filter(fn ($entry) => $this->showHiddenPoints || ! $entry['is_hidden'])
            ->values()
            ->reverse();
    }

    public function mount(Game $game, Player $player)
    {
        $this->game = $game;
        $this->player = $player;
    }

    public function render()
    {
        return view('livewire.player-page');
    }
}
