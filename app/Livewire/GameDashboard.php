<?php

namespace App\Livewire;

use App\Models\Game;
use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Thunk\Verbs\Facades\Verbs;

class GameDashboard extends Component
{
    public Game $game;

    public string $selected_team_id;

    public int $quit_points;

    public array $challenge_component = [];

    public array $challenge_properties = [];

    public array $challenge_validation_rules = [];

    public array $modifier_properties = [];

    public array $modifier_validation_rules = [];

    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function players()
    {
        return $this->game->players;
    }

    #[Computed]
    public function player()
    {
        return $this->players->where('user_id', $this->user->id)
            ->where('status', '!=', 'rejected')
            ->where('status', '!=', 'removed')
            ->first();
    }

    #[Computed]
    public function is_game_admin()
    {
        return $this->user->isGameAdmin($this->game);
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
    public function modifiers()
    {
        return $this->game->modifiers;
    }

    public function mount(Game $game)
    {
        $this->game = $game;

        if (! $this->player || $this->game->status === 'upcoming') {
            return redirect()->route('pre-game-lobby', ['game' => $this->game]);
        }

        $player_needs_to_join_team = $this->game->gameTemplate->type === 'team' && ! $this->player->team;

        $this->challenge_component = $player_needs_to_join_team
            ? []
            : $this->challenge_handler->frontendComponent($this->player);

        $this->challenge_properties = $player_needs_to_join_team
            ? []
            : $this->challenge_handler->propertiesForLivewire($this->player);

        $validation = $player_needs_to_join_team
            ? []
            : $this->challenge_handler->validationRulesForLivewire($this->player);

        $this->challenge_validation_rules = $player_needs_to_join_team
            ? []
            : $validation['rules'];
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

    public function callChallengeAction(string $action, ?array $params = null)
    {
        $params = $params ?? $this->challenge_properties;
    
        $component = $this->challenge_component;
    
        $button = $this->findButtonByAction($component['elements'] ?? [], $action);
    
        $fields = $button['validate'] ?? [];

        dd($button);
        dd($fields);
    
        if (!empty($fields)) {
            $validation = $this->challenge_validation_rules;
    
            $filtered_rules = [];
            $filtered_messages = [];
            foreach ($fields as $field) {
                $key = "challenge_properties.$field";
                if (isset($validation['rules'][$key])) {
                    $filtered_rules[$key] = $validation['rules'][$key];
                }
                if (isset($validation['messages'])) {
                    foreach ($validation['messages'] as $msg_key => $msg_val) {
                        if (str_starts_with($msg_key, "$key.")) {
                            $filtered_messages[$msg_key] = $msg_val;
                        }
                    }
                }
            }

            dd($filtered_rules, $filtered_messages);
    
            $this->validate($filtered_rules, $filtered_messages);
        }
    
        $this->challenge->handler()->{$action}($this->player, $params);
    
        redirect()->route('game-dashboard', ['game' => $this->game]);
    }

    public function callModifierAction(string $modifier_key, string $action, ?array $params = null)
    {
        $params = $params ?? $this->modifier_properties;

        // @todo validate params

        $modifier = $this->modifiers->firstWhere('key', $modifier_key);

        $modifier->handler()->{$action}($this->player, $params);

        redirect()->route('game-dashboard', ['game' => $this->game]);
    }

    protected function findButtonByAction(array $elements, string $action)
    {
        foreach ($elements as $el) {
            if (($el['type'] ?? null) === 'button' && ($el['action'] ?? null) === $action) {
                return $el;
            }
            // If this element has children (e.g., a button_group), search recursively
            if (isset($el['elements']) && is_array($el['elements'])) {
                $found = $this->findButtonByAction($el['elements'], $action);
                if ($found) {
                    return $found;
                }
            }
        }
        return null;
    }

    public function render()
    {
        return view('livewire.game-dashboard');
    }
}
