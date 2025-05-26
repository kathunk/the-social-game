<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\GameTemplate;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CreateGame extends Component
{
    public Carbon $game_start_timecode;

    public int $game_template_id;

    public bool $requires_admin_approval_to_join = false;

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
        if (! $this->user->is_member) {
            return redirect()->route('dashboard');
        }

        $this->game_start_timecode = Carbon::now()->addHours(1)->setSeconds(0);
    }

    public function createGame()
    {
        $this->validate();

        $game = Game::fromTemplate(
            template: GameTemplate::find($this->game_template_id),
            starts_at: $this->game_start_timecode->setSeconds(0),
            user: $this->user,
            requires_admin_approval_to_join: $this->requires_admin_approval_to_join,
        );

        return redirect()->route('pre-game-lobby', $game);
    }

    public function rules()
    {
        return [
            'game_template_id' => 'required|exists:game_templates,id',
            'game_start_timecode' => ['required', 'date', 'after:'.now()],
        ];
    }

    public function render()
    {
        return view('livewire.create-game');
    }
}
