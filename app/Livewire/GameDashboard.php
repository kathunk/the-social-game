<?php

namespace App\Livewire;

use App\Events\UserSwitchedCurrentGame;
use App\Livewire\Concerns\HandlesClassActions;
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
    use HandlesClassActions;

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
    public function gameMode()
    {
        return $this->game->gameMode;
    }

    #[Computed]
    public function footerMessage()
    {
        if (
            $this->game->status !== 'active' ||
            ! $this->gameMode->footer_message
        ) {
            return null;
        }

        return (new HtmlTransformer(
            $this->gameMode->footer_message
        ))->formatted();
    }

    #[Computed]
    public function postGameMessage()
    {
        if (
            $this->game->status !== 'ended' ||
            ! $this->gameMode->post_game_message
        ) {
            return null;
        }

        return (new HtmlTransformer(
            $this->gameMode->post_game_message
        ))->formatted();
    }

    public function mount(Game $game)
    {
        $this->game = $game;

        if (! $this->player || $this->game->status === 'upcoming') {
            return redirect()->route('pre-game-lobby', ['game' => $this->game]);
        }

        $player_needs_to_join_team =
            $this->template->type === 'team' && ! $this->player->team && $this->gameMode->requires_team_membership;

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

    protected function getComputedPropertiesToClear(): array
    {
        return [
            'user',
            'players',
            'player',
            'is_game_admin',
            'teams',
            'current_team',
            'challenge',
            'modifiers',
            'challengeHandler',
            'template',
            'showScoreboard',
            'socialLink',
            'gameMode',
            'footerMessage',
            'postGameMessage',
        ];
    }

    #[On('challenge-complete')]
    public function refreshChallenge()
    {
        // @todo this is a hack to refresh the challenge, but it's not the best way to do it
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
