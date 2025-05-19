<?php

namespace App\Livewire;

use App\Events\UserSwitchedCurrentGame;
use App\Models\Game;
use App\Models\Team;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Thunk\Verbs\Facades\Verbs;

#[On('challenge-complete')]
class GameDashboard extends Component
{
    public Game $game;

    public string $selected_team_id;

    public int $quit_points;

    public array $challenge_component = [];

    public array $modifier_component = [];

    public array $challenge_properties = [];

    public array $modifier_properties = [];

    public array $challenge_validation_rules = [];

    public array $modifier_validation_rules = [];

    public array $modifier_validation_messages = [];

    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function players()
    {
        return $this->game->players->sortByDesc('score');
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
        return $this->challenge?->handler();
    }

    #[Computed]
    public function challengeComponent()
    {
        if (! $this->challenge) {
            return null;
        }

        if ($this->template->type === 'team' && ! $this->current_team) {
            return null;
        }

        return $this->challenge_handler->frontendComponent($this->player);
    }

    #[Computed]
    public function template()
    {
        return $this->game->gameTemplate;
    }

    #[Computed]
    public function modifiers()
    {
        return $this->game->modifiers;
    }

    #[Computed]
    public function showScoreboard()
    {
        return ! $this->challenge_handler::HIDE_SCOREBOARD;
    }

    public function mount(Game $game)
    {
        $this->game = $game;

        if (! $this->player || $this->game->status === 'upcoming') {
            return redirect()->route('pre-game-lobby', ['game' => $this->game]);
        }

        $player_needs_to_join_team = $this->template->type === 'team' && ! $this->player->team;

        if ($player_needs_to_join_team) {
            return;
        }

        if ($this->user->current_game_id !== $this->game->id) {
            UserSwitchedCurrentGame::fire(
                user_id: $this->user->id,
                player_id: $this->player->id,
                game_id: $this->game->id,
            );

            Verbs::commit();
        }

        $this->initializeChallenge();
    }

    protected function initializeChallenge()
    {
        $this->challenge_component = $this->challenge_handler?->frontendComponent($this->player) ?? [];
        $this->challenge_properties = $this->challenge_handler?->propertiesForLivewire($this->player) ?? [];
        $this->challenge_validation_rules = $this->challenge_handler?->validationRulesForLivewire($this->player) ?? [];
    }

    #[On('challenge-complete')]
    public function refreshChallenge()
    {
        $this->initializeChallenge();
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

    public function rules()
    {
        // Get the base rules for challenge_properties
        $rules = [
            'challenge_properties' => 'array',
            'challenge_properties.*' => 'nullable',
        ];

        // If we have validation rules from the challenge handler, merge them
        if (! empty($this->challenge_validation_rules['rules'])) {
            // Transform the rules to be under challenge_properties namespace
            $transformed_rules = [];
            foreach ($this->challenge_validation_rules['rules'] as $key => $rule) {
                $transformed_rules["challenge_properties.$key"] = $rule;
            }
            $rules = array_merge($rules, $transformed_rules);
        }

        return $rules;
    }

    public function callChallengeAction(string $action, ?array $params = null)
    {
        // If no params provided, use challenge_properties
        $params = $params ?? $this->challenge_properties;

        // Always wrap params in challenge_properties namespace
        $params = ['challenge_properties' => $params];

        $component = $this->challenge_handler->frontendComponent($this->player);

        $all_elements = collect($component['elements'])->flatMap(function ($el) {
            return [
                $el,
                ...collect($el['elements'] ?? [])->all(),
                ...collect($el['buttons'] ?? [])->all(),
            ];
        });

        $button = $all_elements->firstWhere(fn ($el) => ($el['type'] ?? null) === 'button' && ($el['action'] ?? null) === $action
        );

        $fields = $button['properties_to_validate'] ?? [];

        if (! empty($fields)) {
            $validation = $this->challenge_validation_rules;

            $filtered_rules = [];
            $filtered_messages = [];
            foreach ($fields as $field) {
                // No need to prefix with challenge_properties here since rules() handles it
                if (isset($validation['rules'][$field])) {
                    $filtered_rules["challenge_properties.$field"] = $validation['rules'][$field];
                }
                if (isset($validation['messages'])) {
                    foreach ($validation['messages'] as $msg_key => $msg_val) {
                        if (str_starts_with($msg_key, "$field.")) {
                            $filtered_messages["challenge_properties.$msg_key"] = $msg_val;
                        }
                    }
                }
            }

            $this->validate($filtered_rules, $filtered_messages);
        }

        // Extract the challenge_properties when passing to the handler
        $this->challenge->handler()->{$action}($this->player, $params['challenge_properties']);

        redirect()->route('game-dashboard', ['game' => $this->game]);
    }

    public function callModifierAction(string $modifier_key, string $action, ?array $params = null)
    {
        $params = $params ?? $this->modifier_properties;

        // @todo validate params

        $modifier = $this->modifiers->firstWhere('class_key', $modifier_key);
        $response = $modifier->handler()->{$action}($this->player, $params);

        return $response instanceof \Illuminate\Http\RedirectResponse
            || $response instanceof \Livewire\Features\SupportRedirects\Redirector
            ? $response
            : redirect()->route('game-dashboard', ['game' => $this->game]);
    }

    public function render()
    {
        return view('livewire.game-dashboard');
    }
}
