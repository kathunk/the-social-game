<?php

namespace App\Livewire;

use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Thunk\Verbs\Facades\Verbs;

class GameDashboard extends Component
{
    public string $selected_team_id;

    public int $quit_points;

    public array $challenge_properties = [];

    public array $challenge_validation_rules = [];

    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function player()
    {
        return $this->user->currentPlayer;
    }

    #[Computed]
    public function game()
    {
        return $this->player->game;
    }

    #[Computed]
    public function teams()
    {
        return $this->game->teams->sortByDesc('score');
    }

    #[Computed]
    public function current_team()
    {
        return $this->player->team;
    }

    #[Computed]
    public function challenge()
    {
        return $this->game->currentChallenge;
    }

    #[Computed]
    public function challengeHandler()
    {
        return $this->challenge->handler();
    }

    #[Computed]
    public function challengeComponent()
    {
        return $this->challenge_handler->frontendComponent($this->player);
    }

    public function mount()
    {
        if (! $this->player || $this->game->status === 'upcoming') {
            return redirect()->route('pre-game-lobby', ['game' => $this->game]);
        }

        $this->challenge_properties = $this->challenge_handler->propertiesForLivewire($this->player);
        $validation = $this->challenge_handler->validationRulesForLivewire($this->player);
        $this->challenge_validation_rules = $validation['rules'];
    }

    public function joinTeam()
    {
        $this->validate([
            'selected_team_id' => 'required|exists:teams,id',
        ]);

        // @todo freaky ass bug where joinTeam fails when you choose the first team in the select

        $team = Team::find($this->selected_team_id);
        $this->player->joinTeam($team);
        $this->selected_team_id = '';

        Verbs::commit();

        redirect()->route('game-dashboard', ['game' => $this->game]);
    }

    public function resign()
    {
        $this->player->resign($this->quit_points);

        Verbs::commit();

        redirect()->route('game-dashboard', ['game' => $this->game]);
    }

    public function callChallengeAction(string $action, ?array $params = null)
    {
        $params = $params ?? $this->challenge_properties;

        if (count($this->challenge_validation_rules) > 0) {
            $validation = $this->challenge_handler->validationRulesForLivewire($this->player);
            $this->validate($validation['rules'], $validation['messages']);
        }

        $this->challenge->handler()->{$action}($this->player, $params);

        redirect()->route('game-dashboard', ['game' => $this->game]);
    }

    public function render()
    {
        return view('livewire.game-dashboard');
    }
}
