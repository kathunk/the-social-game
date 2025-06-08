<?php

namespace App\Livewire;

use App\Events\GameModeUnarchived;
use App\Models\GameMode;
use App\Models\GameTemplate;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Thunk\Verbs\Facades\Verbs;

class GameModesListPage extends Component
{
    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function gameModes()
    {
        return GameMode::all();
    }

    #[Computed]
    public function archivedGameModes()
    {
        return GameMode::withoutGlobalScope('not_archived')
            ->where('is_archived', true)
            ->get();
    }

    #[Computed]
    public function gameTemplates()
    {
        return GameTemplate::all();
    }

    public function mount()
    {
        if (! $this->user->is_super_admin) {
            return redirect()->route('dashboard');
        }
    }

    public function unarchiveMode(string $game_mode_id)
    {
        GameModeUnarchived::fire(game_mode_id: (int) $game_mode_id);

        Verbs::commit();

        return redirect()->route('game-modes.show', $game_mode_id);
    }

    public function render()
    {
        return view('livewire.game-modes-list-page');
    }
}
