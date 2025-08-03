<?php

namespace App\Events;

use App\Challenges\ChallengeRegistry;
use App\Events\Traits\HasGameMode;
use App\Models\GameTemplate;
use App\Modifiers\Classes\BloodOaths;
use App\Modifiers\ModifierRegistry;
use App\States\GameModeState;
use App\States\GameTemplateState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class GameTemplateAdded extends Event
{
    use HasGameMode;

    #[StateId(GameTemplateState::class)]
    public ?int $game_template_id = null;

    public string $name;

    public string $type;

    public array $team_names;

    public array $challenges;

    public bool $is_public;

    public ?array $modifiers = [];

    public function validate()
    {
        $challenge_keys = collect($this->challenges)->map(fn ($c) => $c['challenge_keys'])->flatten()->unique();

        $modifier_keys = collect($this->modifiers);

        $challenge_validation_errors = $challenge_keys
            ->map(fn ($c) => ChallengeRegistry::retrieveFromKey($c)
                ->isInvalidForTemplate($this->challenges, $this->modifiers, $this->type, $this->team_names)
            )
            ->filter(fn ($error) => $error !== false);

        $modifier_validation_errors = $modifier_keys
            ->map(fn ($m) => ModifierRegistry::retrieveFromKey($m)
                ->isInvalidForTemplate($this->challenges, $this->modifiers, $this->type, $this->team_names)
            )
            ->filter(fn ($error) => $error !== false);

        $this->assert(
            $challenge_validation_errors->isEmpty(),
            'The following challenges are invalid for this template: '.$challenge_validation_errors->implode(', ')
        );

        $this->assert(
            $modifier_validation_errors->isEmpty(),
            'The following modifiers are invalid for this template: '.$modifier_validation_errors->implode(', ')
        );

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
        });
    }

    public function applyToGameTemplate(GameTemplateState $game_template)
    {
        $mode = $this->state(GameModeState::class);

        $game_template->name = $this->name;
        $game_template->type = $this->type;
        $game_template->team_names = $this->team_names;
        $game_template->challenges = $this->challenges;
        $game_template->modifiers = $this->modifiers;
        $game_template->is_public = $this->is_public;
        $game_template->game_mode_id = $this->game_mode_id;
        $game_template->min_players = $mode->min_players;
        $game_template->max_players = $mode->max_players;
        $game_template->players_can_join_late = $mode->players_can_join_late;

        if (in_array(BloodOaths::key(), $this->modifiers)) {
            $game_template->scoreboard_type = 'blood_oath';
        } else {
            $game_template->scoreboard_type = $this->type;
        }
    }

    public function applyToGameMode(GameModeState $game_mode)
    {
        if ($game_mode->game_template_ids->contains($this->game_template_id)) {
            return;
        }

        $game_mode->game_template_ids->push($this->game_template_id);
    }

    public function handle()
    {
        $existing = GameTemplate::find($this->game_template_id);

        $scoreboard_type = $this->state(GameTemplateState::class)->scoreboard_type;

        $mode = $this->gameMode();

        if ($existing) {
            $existing->update([
                'name' => $this->name,
                'type' => $this->type,
                'is_public' => $this->is_public,
                'team_names' => $this->team_names,
                'challenges' => $this->challenges,
                'modifiers' => $this->modifiers,
                'scoreboard_type' => $scoreboard_type,
                'game_mode_id' => $this->game_mode_id,
                'description' => $mode->description,
                'pre_game_lobby_message' => $mode->pre_game_lobby_message,
                'min_players' => $mode->min_players,
                'max_players' => $mode->max_players,
                'players_can_join_late' => $mode->players_can_join_late,
            ]);

            return;
        }

        GameTemplate::create([
            'id' => $this->game_template_id,
            'name' => $this->name,
            'type' => $this->type,
            'is_public' => $this->is_public,
            'team_names' => $this->team_names,
            'challenges' => $this->challenges,
            'modifiers' => $this->modifiers,
            'scoreboard_type' => $scoreboard_type,
            'game_mode_id' => $this->game_mode_id,
            'description' => $mode->description,
            'pre_game_lobby_message' => $mode->pre_game_lobby_message,
            'min_players' => $mode->min_players,
            'max_players' => $mode->max_players,
            'players_can_join_late' => $mode->players_can_join_late,
        ]);
    }
}
