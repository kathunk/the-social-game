<?php

namespace App\Livewire;

use App\Models\Game;
use Livewire\Component;
use App\Models\GameTemplate;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;

class CreateGame extends Component
{
    public Carbon $game_start_timecode;

    public int $game_template_id;

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

        $this->game_start_timecode = Carbon::now()->addHours(1);
    }

    public function createGame()
    {
        $this->validate();

        $game_start_time_rounded_down = Carbon::parse($this->game_start_timecode)->setSeconds(0);

        $game = Game::fromTemplate(
            GameTemplate::find($this->game_template_id),
            $game_start_time_rounded_down,
            $this->user,
        );

        return redirect()->route('pre-game-lobby', $game);
    }

    public function rules()
    {
        return [
            'game_template_id' => 'required|exists:game_templates,id',
            'game_start_timecode' => 'required|date|after:now',
        ];
    }

    public function render()
    {
        return view('livewire.create-game');
    }
}
