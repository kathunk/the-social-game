<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\GameTemplate;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;

class CreateGame extends Component
{
    public Carbon $game_start_timecode;

    #[Computed]
    public function game_templates()
    {
        if ($this->user->is_super_admin) {
            return GameTemplate::all();
        }

        return GameTemplate::where('is_public', true)->get();
    }

    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    public function mount()
    {
        if (!$this->user->is_member) {
            return redirect()->route('dashboard');
        }

        $this->starts_at = Carbon::now()->addHours(1);
    }

    public function createGame()
    {
        $game_start_time_rounded_down = Carbon::parse($this->game_start_timecode)->setSeconds(0);

        CreateGame::fire(
            
        )
    }

    public function render()
    {
        return view('livewire.create-game');
    }
}
