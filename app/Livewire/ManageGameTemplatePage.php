<?php

namespace App\Livewire;

use App\Challenges\ChallengeRegistry;
use App\Events\GameTemplateAdded;
use App\Events\GameTemplateArchived;
use App\Models\GameMode;
use App\Models\GameTemplate;
use App\Modifiers\ModifierRegistry;
use Exception;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Thunk\Verbs\Facades\Verbs;

class ManageGameTemplatePage extends Component
{
    public ?GameTemplate $game_template = null;

    public string $name;

    public string $description;

    public string $pre_game_lobby_message;

    public ?int $min_players;

    public ?int $max_players;

    public bool $is_public;

    public ?string $team_names;

    public array $challenges;

    public bool $players_can_join_late;

    public GameMode $game_mode;

    public string $game_type;

    public array $modifiers;

    #[Computed]
    public function allChallenges()
    {
        return collect(ChallengeRegistry::getAll())->filter(fn ($c) => $c::TYPE === $this->game_type)->sortBy(fn ($c) => $c::NAME);
    }

    #[Computed]
    public function allModifiers()
    {
        return collect(ModifierRegistry::getAll())->filter(fn ($m) => $m::TYPE === $this->game_type)->sortBy(fn ($m) => $m::NAME);
    }

    public function mount($game_mode, $game_template = null)
    {
        $this->game_mode = $game_mode;

        if ($game_template instanceof GameTemplate) {
            $this->game_template = $game_template;

            $this->name = $game_template->name ?? '';
            $this->description = $game_template->description ?? '';
            $this->pre_game_lobby_message = $game_template->pre_game_lobby_message ?? '';
            $this->min_players = $game_template->min_players ?? null;
            $this->max_players = $game_template->max_players ?? null;
            $this->is_public = $game_template->is_public ?? false;
            $this->team_names = implode(', ', $game_template->team_names) ?? '';
            $this->challenges = $game_template->challenges ?? [];
            $this->modifiers = $game_template->modifiers ?? [];
            $this->players_can_join_late = $game_template->players_can_join_late ?? false;
            $this->game_type = $game_template->type ?? 'individual';
        } else {
            $this->name = '';
            $this->description = '';
            $this->pre_game_lobby_message = '';
            $this->min_players = $this->game_mode->min_players ?? null;
            $this->max_players = $this->game_mode->max_players ?? null;
            $this->is_public = false;
            $this->team_names = '';
            $this->challenges = [];
            $this->modifiers = [];
            $this->players_can_join_late = $this->game_mode->players_can_join_late ?? false;
            $this->game_type = $this->game_mode->type;
        }
    }

    public function addChallenge()
    {
        $this->challenges[] = [
            'challenge_keys' => [],
            'duration' => null,
        ];
    }

    public function removeChallenge($index)
    {
        unset($this->challenges[$index]);
    }

    public $rules = [
        'name' => 'required|string|max:100',
        'is_public' => 'boolean',
        'team_names' => 'nullable|string',
        'challenges' => 'required|array|min:1',
        'challenges.*.challenge_keys' => 'required|array|min:1',
        'challenges.*.duration' => 'nullable|integer|min:1',
    ];

    public function saveTemplate()
    {
        $this->validate();

        $teams = array_map('trim', explode(',', $this->team_names));

        $challenges = array_map(function ($challenge) {
            $challenge['duration'] = (int) $challenge['duration'];

            return $challenge;
        }, $this->challenges);

        $id = $this->game_template->id ?? null;

        try {
            $new_id = GameTemplateAdded::fire(
                game_template_id: $id,
                game_mode_id: $this->game_mode->id,
                name: $this->name,
                description: $this->description,
                pre_game_lobby_message: $this->pre_game_lobby_message,
                type: $this->game_type,
                min_players: $this->min_players ?? null,
                max_players: $this->max_players ?? null,
                is_public: $this->is_public,
                team_names: $teams,
                challenges: $challenges,
                modifiers: $this->modifiers,
                players_can_join_late: $this->players_can_join_late,
            )->game_template_id;
        } catch (Exception $e) {
            $this->addError('error', $e->getMessage());

            return;
        }

        Verbs::commit();

        return redirect()->route('game-templates.show', ['game_mode' => $this->game_mode->id, 'game_template' => $new_id]);
    }

    public function duplicateTemplate()
    {
        $id = GameTemplateAdded::fire(
            name: $this->game_template->name.' (copy)',
            game_mode_id: $this->game_template->game_mode_id,
            description: $this->game_template->description,
            pre_game_lobby_message: $this->game_template->pre_game_lobby_message,
            type: $this->game_type,
            min_players: $this->game_template->min_players ?? null,
            max_players: $this->game_template->max_players ?? null,
            is_public: $this->game_template->is_public,
            team_names: $this->game_template->team_names,
            challenges: $this->game_template->challenges,
            modifiers: $this->game_template->modifiers,
            players_can_join_late: $this->game_template->players_can_join_late,
        )->game_template_id;

        Verbs::commit();

        return redirect()->route('game-templates.show', ['game_mode' => $this->game_template->game_mode_id, 'game_template' => $id]);
    }

    public function archiveTemplate()
    {
        GameTemplateArchived::fire(game_template_id: $this->game_template->id);

        Verbs::commit();

        return redirect()->route('game-modes.index');
    }

    public function render()
    {
        return view('livewire.manage-game-template-page');
    }
}
