<?php

namespace App\Livewire;

use App\Models\Game;
use Livewire\Component;
use App\Models\Modifier;
use Livewire\Attributes\Computed;

class SecretsPage extends Component
{
    public Game $game;

    public Modifier $modifier;

    #[Computed]
    public function player()
    {
        return $this->game->players->firstWhere('user_id', $this->user->id);
    }

    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    public function mount(Game $game, Modifier $modifier)
    {
        $this->game = $game;
        $this->modifier = $modifier;

        $this->modifier->handler()->onSecretDiscovered($this->player);
    }

    public function callModifierAction(string $modifier_key, string $action, ?array $params = null)
    {
        $params = $params ?? $this->modifier_properties;

        // @todo validate params

        $this->modifier->handler()->{$action}($this->player, $params);

        $response = $this->modifier->handler()->{$action}($this->player, $params);

        return $response instanceof \Illuminate\Http\RedirectResponse
            || $response instanceof \Livewire\Features\SupportRedirects\Redirector
            ? $response
            : redirect()->route('game-dashboard', ['game' => $this->game]);
    }

    public function render()
    {
        return view('livewire.secrets-page');
    }
}
