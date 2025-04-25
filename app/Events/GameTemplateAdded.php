<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\Models\GameTemplate;
use App\Events\Traits\HasUser;
use App\States\GameTemplateState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

class GameTemplateAdded extends Event
{
    #[StateId(GameTemplateState::class)]
    public ?int $game_template_id = null;

    public string $name;

    public string $type;

    public ?int $min_players;

    public ?int $max_players;

    public bool $is_public;

    public array $team_names;

    public array $challenges;

    public function applyToGameTemplate(GameTemplateState $game_template)
    {
        $game_template->name = $this->name;
        $game_template->type = $this->type;
        $game_template->min_players = $this->min_players;
        $game_template->max_players = $this->max_players;
        $game_template->is_public = $this->is_public;
        $game_template->team_names = $this->team_names;
        $game_template->challenges = $this->challenges;
    }

    public function handle()
    {
        $existing = GameTemplate::find($this->game_template_id);

        if ($existing) {
            $existing->update([
                'name' => $this->name,
                'type' => $this->type,
                'min_players' => $this->min_players,
                'max_players' => $this->max_players,
                'is_public' => $this->is_public,
                'team_names' => $this->team_names,
                'challenges' => $this->challenges,
            ]);

            return;
        }
        
        GameTemplate::create([
            'id' => $this->game_template_id,
            'name' => $this->name,
            'type' => $this->type,
            'min_players' => $this->min_players,
            'max_players' => $this->max_players,
            'is_public' => $this->is_public,
            'team_names' => $this->team_names,
            'challenges' => $this->challenges,
        ]);
    }
}
