<?php

namespace App\Livewire;

use App\Events\UserSwitchedCurrentGame;
use App\Models\Game;
use App\Models\Team;
use App\Support\HtmlTransformer;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Thunk\Verbs\Facades\Verbs;

#[On('challenge-complete')]
class GameDashboard extends Component
{
    public Game $game;

    public string $selected_team_id;

    public array $round_properties = [];

    public array $validation_rules = [];

    public ?array $challenge_component = [];

    public ?array $modifier_components = [];

    #[Computed]
    public function user()
    {
        return auth()->user();
    }

    #[Computed]
    public function players()
    {
        return $this->game->status === 'ended'
            ? $this->game->players->sortByDesc('hidden_score')
            : $this->game->players->sortByDesc('score');
    }

    #[Computed]
    public function player()
    {
        return $this->players
            ->where('user_id', $this->user->id)
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
        $sort_by = $this->game->status === 'ended' ? 'hidden_score' : 'score';

        return $this->game->teams->sortByDesc($sort_by);
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
    public function modifiers()
    {
        return $this->game->modifiers;
    }

    #[Computed]
    public function challengeHandler()
    {
        return $this->challenge?->handler();
    }

    #[Computed]
    public function template()
    {
        return $this->game->gameTemplate;
    }

    #[Computed]
    public function showScoreboard()
    {
        if (! $this->challenge_handler) {
            return true;
        }

        return ! $this->challenge_handler::HIDE_SCOREBOARD;
    }

    #[Computed]
    public function socialLink()
    {
        return $this->game->social_links[0] ?? null;
    }

    #[Computed]
    public function footerMessage()
    {
        if (
            $this->game->status !== 'active' ||
            ! $this->game->gameMode->footer_message
        ) {
            return null;
        }

        return (new HtmlTransformer(
            $this->game->gameMode->footer_message
        ))->formatted();
    }

    #[Computed]
    public function postGameMessage()
    {
        if (
            $this->game->status !== 'ended' ||
            ! $this->game->gameMode->post_game_message
        ) {
            return null;
        }

        return (new HtmlTransformer(
            $this->game->gameMode->post_game_message
        ))->formatted();
    }

    public function mount(Game $game)
    {
        $this->game = $game;

        if (! $this->player || $this->game->status === 'upcoming') {
            return redirect()->route('pre-game-lobby', ['game' => $this->game]);
        }

        $player_needs_to_join_team =
            $this->template->type === 'team' && ! $this->player->team;

        if ($player_needs_to_join_team) {
            return;
        }

        if ($this->user->current_game_id !== $this->game->id) {
            UserSwitchedCurrentGame::fire(
                user_id: $this->user->id,
                player_id: $this->player->id,
                game_id: $this->game->id
            );

            Verbs::commit();
        }

        $this->initializeProperties();
    }

    protected function initializeProperties()
    {
        $this->challenge_component = $this->game->currentChallenge
            ?->fresh()
            ->handler()
            ->frontendComponent($this->player);

        if ($this->challenge) {
            $this->round_properties[$this->challenge->class_key] =
                $this->challenge_handler?->propertiesForLivewire(
                    $this->player
                ) ?? [];
            $this->validation_rules[$this->challenge->class_key] =
                $this->challenge_handler?->validationRulesForLivewire(
                    $this->player
                ) ?? [];
        }

        if ($this->modifiers->count() === 0) {
            return;
        }

        foreach ($this->modifiers as $modifier) {
            $this->round_properties[$modifier->class_key] =
                $modifier->handler()?->propertiesForLivewire($this->player) ??
                [];

            $this->validation_rules[$modifier->class_key] =
                $modifier
                    ->handler()
                    ?->validationRulesForLivewire($this->player) ?? [];

            $this->modifier_components[$modifier->class_key] = $modifier
                ->handler()
                ->frontendComponent($this->player);
        }
    }

    #[On('challenge-complete')]
    public function refreshChallenge()
    {
        $this->initializeProperties();
    }

    public function joinTeam()
    {
        $this->validate([
            'selected_team_id' => 'required|exists:teams,id',
        ]);

        $team = Team::find($this->selected_team_id);
        $this->player->joinTeam($team);
        $this->selected_team_id = '';

        Verbs::commit();

        redirect()->route('game-dashboard', ['game' => $this->game]);
    }

    public function rules()
    {
        $rules = [
            'round_properties' => 'array',
            'round_properties.*' => 'nullable',
        ];

        foreach ($this->validation_rules as $class_key => $validation) {
            if (! empty($validation['rules'])) {
                $transformed_rules = [];
                foreach ($validation['rules'] as $key => $rule) {
                    $transformed_rules[
                        "round_properties.$class_key.$key"
                    ] = $rule;
                }
                $rules = array_merge($rules, $transformed_rules);
            }
        }

        if (isset($this->filtered_rules)) {
            $rules = array_merge($rules, $this->filtered_rules);
        }

        return $rules;
    }

    public function callClassAction(
        string $action,
        string $type,
        string $class_key
    ) {
        $params = $this->round_properties[$class_key];
        $params = ['round_properties' => $params];

        $handler = match ($type) {
            'challenge' => $this->challenge_handler,
            'modifier' => $this->modifiers
                ->firstWhere('class_key', $class_key)
                ->handler(),
        };

        $component = match ($type) {
            'challenge' => $this->challenge_component,
            'modifier' => $this->modifier_components[$class_key],
        };

        if (! isset($component['elements'])) {
            return;
        }

        $all_elements = collect($component['elements'])->flatMap(function (
            $el
        ) {
            return [
                $el,
                ...collect($el['elements'] ?? [])->all(),
                ...collect($el['buttons'] ?? [])->all(),
            ];
        });

        $button = $all_elements->firstWhere(
            fn ($el) => ($el['type'] ?? null) === 'button' &&
                ($el['action'] ?? null) === $action
        );

        $fields = $button['properties_to_validate'] ?? [];

        if (! empty($fields)) {
            $validation = $this->validation_rules[$class_key] ?? [];

            $filtered_rules = [];
            $filtered_messages = [];
            foreach ($fields as $field) {
                if (isset($validation['rules'][$field])) {
                    $filtered_rules["round_properties.$class_key.$field"] =
                        $validation['rules'][$field];
                }
                if (isset($validation['messages'])) {
                    foreach ($validation['messages'] as $msg_key => $msg_val) {
                        if (str_starts_with($msg_key, "$field.")) {
                            $filtered_messages[
                                "round_properties.$class_key.$msg_key"
                            ] = $msg_val;
                        }
                    }
                }
            }

            $this->validate($filtered_rules, $filtered_messages);
        }

        try {
            $response = $handler->{$action}(
                $this->player,
                $params['round_properties']
            );
        } catch (\Exception $e) {
            $this->addError('error', $e->getMessage());

            return;
        }

        Verbs::commit();

        if ($type === 'challenge') {
            $this->challenge_component = $this->game->currentChallenge
                ?->fresh()
                ->handler()
                ->frontendComponent($this->player->fresh());
        }

        if ($type === 'modifier') {
            $modifier = $this->game
                ->fresh()
                ->modifiers()
                ->where('class_key', $class_key)
                ->first();

            $this->round_properties[$modifier->class_key] =
                $modifier
                    ->handler()
                    ?->propertiesForLivewire($this->player->fresh()) ?? [];

            $this->validation_rules[$modifier->class_key] =
                $modifier
                    ->handler()
                    ?->validationRulesForLivewire($this->player->fresh()) ?? [];

            $this->modifier_components[$modifier->class_key] = $modifier
                ->fresh()
                ->handler()
                ->frontendComponent($this->player->fresh());
        }

        return $response instanceof \Illuminate\Http\RedirectResponse ||
            $response instanceof \Livewire\Features\SupportRedirects\Redirector
            ? $response
            : null;
    }

    #[On('echo-private:games.{game.id},GameUpdatedForReverb')]
    public function refreshGame()
    {
        return redirect()->route('game-dashboard', ['game' => $this->game]);
    }

    public function render()
    {
        return view('livewire.game-dashboard');
    }
}
