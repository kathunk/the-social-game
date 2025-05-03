<?php

namespace App\Events;

use App\Challenges\ChallengeRegistry;
use App\Models\GameTemplate;
use App\Modifiers\ModifierRegistry;
use App\States\GameTemplateState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class GameTemplateAdded extends Event
{
    #[StateId(GameTemplateState::class)]
    public ?int $game_template_id = null;

    public string $name;

    public string $description;

    public string $type;

    public ?int $min_players;

    public ?int $max_players;

    public bool $is_public;

    public array $team_names;

    public array $challenges;

    public ?array $modifiers = [];

    public string $pre_game_lobby_message;

    public bool $players_can_join_late;

    public function validate()
    {
        $this->assert(
            GameTemplate::all()->where('id', '!=', $this->game_template_id)->where('name', $this->name)->isEmpty(),
            'A template with this name already exists.'
        );

        $challenge_keys = collect($this->challenges)->map(fn ($c) => $c['challenge_keys'])
            ->flatten()
            ->map(fn ($c) => ChallengeRegistry::retrieveFromKey($c));

        $modifier_keys = collect($this->modifiers)->map(fn ($c) => ModifierRegistry::retrieveFromKey($c));

        $keys = $challenge_keys->merge($modifier_keys);

        $this->assert(
            $keys->map(fn ($c) => $c::TYPE)->unique()->count() === 1,
            'All challenges and modifiers must be of the same type.'
        );

        $this->assert(
            $keys->first()::TYPE === $this->type,
            'The challenge type of all challenges and modifiers must match the game type.'
        );

        collect($this->challenges)->each(function ($challenge) {
            $this->assert(
                collect($challenge['challenge_keys'])->isNotEmpty(),
                'Challenge keys are required.'
            );

            $this->assert(
                $challenge['duration'] > 0,
                'Challenge duration must be greater than 0.'
            );
        });
    }

    public function applyToGame(GameTemplateState $game_template)
    {
        $game_template->name = $this->name;
        $game_template->type = $this->type;
        $game_template->min_players = $this->min_players;
        $game_template->max_players = $this->max_players;
        $game_template->is_public = $this->is_public;
        $game_template->team_names = $this->team_names;
        $game_template->challenges = $this->challenges;
        $game_template->modifiers = $this->modifiers;
        $game_template->description = $this->description;
        $game_template->pre_game_lobby_message = $this->pre_game_lobby_message;
        $game_template->players_can_join_late = $this->players_can_join_late;
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
                'modifiers' => $this->modifiers,
                'description' => $this->description,
                'pre_game_lobby_message' => $this->pre_game_lobby_message,
                'players_can_join_late' => $this->players_can_join_late,
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
            'modifiers' => $this->modifiers,
            'description' => $this->description,
            'pre_game_lobby_message' => $this->pre_game_lobby_message,
            'players_can_join_late' => $this->players_can_join_late,
        ]);
    }
}
