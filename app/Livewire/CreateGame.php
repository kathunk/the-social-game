<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\GameMode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CreateGame extends Component
{
    public Carbon $game_start_timecode;

    public int $game_mode_id;

    public bool $requires_admin_approval_to_join = false;

    public string $social_link_url = '';

    #[Computed]
    public function game_modes()
    {
        if ($this->user->is_super_admin) {
            return GameMode::all();
        }

        return GameMode::where('is_public', true)->get();
    }

    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    public function mount()
    {
        if (! $this->user->is_member) {
            return redirect()->route('marketing-page');
        }

        $this->game_start_timecode = Carbon::now()->addHours(1)->setSeconds(0);
    }

    public function createGame()
    {
        $this->validate();

        $url_input = $this->social_link_url;

        // Only process URL if it's not empty
        if (! empty($url_input)) {
            if (! str_starts_with($url_input, 'http://') && ! str_starts_with($url_input, 'https://')) {
                $url_input = 'https://'.$url_input;
            }

            Validator::make(['url' => $url_input], ['url' => 'url'])->validate();
        }

        $mode = GameMode::find($this->game_mode_id);

        $template = $mode->selectTemplateForUser($this->user);

        $game = Game::fromTemplate(
            game_mode: $mode,
            template: $template,
            starts_at: Carbon::parse($this->game_start_timecode)->setSeconds(0),
            user: $this->user,
            requires_admin_approval_to_join: $this->requires_admin_approval_to_join,
            social_links: ! empty($url_input) ? [$url_input] : [],
        );

        return redirect()->route('pre-game-lobby', $game);
    }

    public function rules()
    {
        return [
            'game_mode_id' => 'required|exists:game_modes,id',
            'game_start_timecode' => ['required', 'date', 'after:'.now()],
        ];
    }

    public function render()
    {
        return view('livewire.create-game');
    }
}
