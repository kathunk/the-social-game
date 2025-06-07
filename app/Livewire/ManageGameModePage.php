<?php

namespace App\Livewire;

use Exception;
use Flux\Flux;
use Livewire\Component;
use App\Models\GameMode;
use App\Models\GameTemplate;
use App\Events\GameModeAdded;
use Thunk\Verbs\Facades\Verbs;
use App\Events\GameModeArchived;
use App\Events\GameTemplateAdded;
use Livewire\Attributes\Computed;
use App\Events\GameModeUnarchived;
use App\Modifiers\ModifierRegistry;
use App\Events\GameTemplateArchived;
use App\Challenges\ChallengeRegistry;
use App\Events\GameTemplateUnarchived;

class ManageGameModePage extends Component
{
    public ?GameMode $game_mode = null;

    public string $name;

    public string $description;

    public string $pre_game_lobby_message;

    public ?int $min_players;

    public ?int $max_players;

    public bool $is_public;

    public bool $players_can_join_late;

    public string $game_type;

    public function mount($game_mode = null)
    {
        if ($game_mode instanceof GameMode) {
            $this->game_mode = $game_mode;

            $this->name = $game_mode->name ?? '';
            $this->description = $game_mode->description ?? '';
            $this->pre_game_lobby_message = $game_mode->pre_game_lobby_message ?? '';
            $this->min_players = $game_mode->min_players ?? null;
            $this->max_players = $game_mode->max_players ?? null;
            $this->is_public = $game_mode->is_public ?? false;
            $this->players_can_join_late = $game_mode->players_can_join_late ?? false;
            $this->game_type = $game_mode->type ?? 'individual';
        } else {
            $this->name = '';
            $this->description = '';
            $this->pre_game_lobby_message = '';
            $this->min_players = null;
            $this->max_players = null;
            $this->is_public = false;
            $this->players_can_join_late = false;
            $this->game_type = 'individual';
        }
    }

    public $rules = [
        'name' => 'required|string|max:100',
        'description' => 'required|string',
        'pre_game_lobby_message' => 'required|string',
        'min_players' => 'nullable|integer',
        'max_players' => 'nullable|integer',
        'is_public' => 'boolean',
        'players_can_join_late' => 'boolean',
        'game_type' => 'required|string|in:individual,team',
    ];

    public function saveGameMode()
    {
        $this->validate();

        $id = $this->game_mode->id ?? null;

        try {
            GameModeAdded::fire(
                game_mode_id: $id,
                name: $this->name,
                description: $this->description,
                pre_game_lobby_message: $this->pre_game_lobby_message,
                type: $this->game_type,
                min_players: $this->min_players ?? null,
                max_players: $this->max_players ?? null,
                is_public: $this->is_public,
                players_can_join_late: $this->players_can_join_late,
            );
        } catch (Exception $e) {
            $this->addError('error', $e->getMessage());

            return;
        }

        Verbs::commit();

        Flux::toast('Game mode saved');
    }

    public function archiveGameMode()
    {
        GameModeArchived::fire(game_mode_id: $this->game_mode->id);

        Verbs::commit();

        return redirect()->route('game-modes.index');
    }

    public function unarchiveGameMode()
    {
        GameModeUnarchived::fire(game_mode_id: $this->game_mode->id);

        Verbs::commit();

        return redirect()->route('game-modes.show', $this->game_mode->id);
    }

    public function render()
    {
        return view('livewire.manage-game-mode-page');
    }
}
