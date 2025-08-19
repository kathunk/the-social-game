<?php

namespace App\States;

use Illuminate\Support\Collection;
use Thunk\Verbs\State;

class GameModeState extends State
{
    public string $name;

    public string $type;

    public ?int $min_players = null;

    public ?int $max_players = null;

    public bool $is_public;

    public bool $players_can_join_late;

    public string $pre_game_lobby_message;

    public bool $is_archived;

    public string $description;

    public ?string $footer_message = '';

    public ?string $post_game_message = '';

    public string $scoreboard_type = 'individual';

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
