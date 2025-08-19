<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\Models\GameMode;
use App\Models\GameTemplate;
use App\States\GameModeState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

class GameModeAdded extends Event
{
    #[StateId(GameModeState::class)]
    public ?int $game_mode_id = null;

    public string $name;

    public string $description;

    public string $type;

    public ?int $min_players;

    public ?int $max_players;

    public bool $is_public;

    public string $pre_game_lobby_message;

    public bool $players_can_join_late;

    public ?string $footer_message = '';

    public ?string $post_game_message = '';

    public ?string $scoreboard_type = 'individual';

    public function validate()
    {
        $this->assert(
            GameMode::all()->where('id', '!=', $this->game_mode_id)->where('name', $this->name)->isEmpty(),
            'A game mode with this name already exists.'
        );

        $this->assert(
            $this->min_players <= $this->max_players,
            'Minimum players must be less than or equal to maximum players.'
        );
    }

    public function applyToGame(GameModeState $game_mode)
    {
        $game_mode->name = $this->name;
        $game_mode->type = $this->type;
        $game_mode->min_players = $this->min_players;
        $game_mode->max_players = $this->max_players;
        $game_mode->is_public = $this->is_public;
        $game_mode->description = $this->description;
        $game_mode->pre_game_lobby_message = $this->pre_game_lobby_message;
        $game_mode->players_can_join_late = $this->players_can_join_late;
        $game_mode->footer_message = $this->footer_message;
        $game_mode->post_game_message = $this->post_game_message;
        $game_mode->scoreboard_type = $this->scoreboard_type;

        $game_mode->gameTemplates()->each(function ($game_template) {
            $game_template->scoreboard_type = $this->scoreboard_type;
        });
    }

    public function handle()
    {
        $existing = GameMode::find($this->game_mode_id);

        if ($existing) {
            $existing->update([
                'name' => $this->name,
                'type' => $this->type,
                'min_players' => $this->min_players,
                'max_players' => $this->max_players,
                'is_public' => $this->is_public,
                'description' => $this->description,
                'pre_game_lobby_message' => $this->pre_game_lobby_message,
                'players_can_join_late' => $this->players_can_join_late,
                'footer_message' => $this->footer_message,
                'post_game_message' => $this->post_game_message,
                'scoreboard_type' => $this->scoreboard_type,
            ]);

            $this->setTemplateScoreboardType($existing);

            return;
        }

        $mode =GameMode::create([
            'id' => $this->game_mode_id,
            'name' => $this->name,
            'type' => $this->type,
            'min_players' => $this->min_players,
            'max_players' => $this->max_players,
            'is_public' => $this->is_public,
            'description' => $this->description,
            'pre_game_lobby_message' => $this->pre_game_lobby_message,
            'players_can_join_late' => $this->players_can_join_late,
            'footer_message' => $this->footer_message,
            'post_game_message' => $this->post_game_message,
            'scoreboard_type' => $this->scoreboard_type,
        ]);

        $this->setTemplateScoreboardType($mode);
    }

    public function setTemplateScoreboardType(GameMode $mode)
    {
        $mode->gameTemplates->each(function ($game_template) {
            $game_template->scoreboard_type = $this->scoreboard_type;
            $game_template->save();
        });
    }
}
