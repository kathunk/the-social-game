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
    public bool $has_scheduled_start = false;

    public ?string $game_start_timecode = null;

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

    public function createGame()
    {
        $this->validate();

        $url_input = $this->social_link_url;

        if (! empty($url_input)) {
            if (
                ! str_starts_with($url_input, 'http://') &&
                ! str_starts_with($url_input, 'https://')
            ) {
                $url_input = 'https://'.$url_input;
            }

            Validator::make(
                ['url' => $url_input],
                ['url' => 'url']
            )->validate();
        }

        $mode = GameMode::find($this->game_mode_id);

        $template = $mode->selectTemplateForUser($this->user);

        $game = Game::fromTemplate(
            game_mode: $mode,
            template: $template,
            starts_at: $this->has_scheduled_start
                ? Carbon::parse($this->game_start_timecode)->setSeconds(0)
                : null,
            user: $this->user,
            requires_admin_approval_to_join: $this->requires_admin_approval_to_join,
            social_links: ! empty($url_input) ? [$url_input] : []
        );

        return redirect()->route('pre-game-lobby', $game);
    }

    public function rules()
    {
        $rules = [
            'game_mode_id' => 'required|exists:game_modes,id',
        ];

        if ($this->has_scheduled_start) {
            $rules['game_start_timecode'] = [
                'required',
                'date',
                'after:'.now(),
            ];
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'game_start_timecode.required' => 'Scheduled start time is required',
            'game_start_timecode.after' => 'Scheduled start must be in the future',
        ];
    }

    public function render()
    {
        return view('livewire.create-game');
    }
}
