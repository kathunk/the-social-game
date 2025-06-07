<?php

namespace App\States;

use Thunk\Verbs\State;
use App\States\GameTemplateState;
use Illuminate\Support\Collection;

class GameModeState extends State
{
    public string $name;
    public string $type;
    public int $min_players;
    public int $max_players;
    public bool $is_public;
    public bool $players_can_join_late;
    public string $pre_game_lobby_message;
    public bool $is_archived;
    public string $description;

    public Collection $game_template_ids;

    public function __construct()
    {
        $this->game_template_ids = collect();
    }

    public function gameTemplates()
    {
        return $this->game_template_ids->map(fn ($id) => GameTemplateState::load($id));
    }
}
