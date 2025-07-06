<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasGameMode;
use App\Events\Traits\HasGameTemplate;
use App\Models\Game;
use App\States\GameModeState;
use App\States\GameState;
use App\States\GameTemplateState;
use App\States\UserState;
use Illuminate\Support\Carbon;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class GameUpdated extends Event
{
    use HasGame, HasGameMode, HasGameTemplate;

    #[StateId(UserState::class)]
    public ?int $user_id = null;

    public ?int $challenge_length_override = null;

    public ?Carbon $starts_at = null;

    public ?Carbon $ends_at = null;

    public bool $is_public;

    public bool $requires_admin_approval_to_join;

    public ?array $social_links = null;

    public function validate()
    {
        $this->assert(
            $this->state(GameModeState::class)->game_template_ids->contains($this->game_template_id),
            'Game template is not valid for this game mode.'
        );
    }

    public function apply(GameState $game)
    {
        $game->game_template_id = $this->game_template_id;
        $game->game_mode_id = $this->game_mode_id;
        $game->starts_at = $this->starts_at ? Carbon::parse($this->starts_at) : null;
        $game->ends_at = $this->ends_at ? Carbon::parse($this->ends_at) : null;
        $game->is_public = $this->is_public;
        $game->requires_admin_approval_to_join = $this->requires_admin_approval_to_join;
        $game->players_can_join_late = $this->state(GameTemplateState::class)->players_can_join_late;
        $game->challenge_length_override = $this->challenge_length_override;
        $game->social_links = $this->social_links;
    }

    public function handle()
    {
        $state = $this->state(GameState::class);

        Game::find($this->game_id)->update([
            'game_template_id' => $this->game_template_id,
            'game_mode_id' => $this->game_mode_id,
            'starts_at' => $state->starts_at,
            'ends_at' => $state->ends_at,
            'is_public' => $this->is_public,
            'requires_admin_approval_to_join' => $this->requires_admin_approval_to_join,
            'players_can_join_late' => $this->state(GameTemplateState::class)->players_can_join_late,
            'challenge_length_override' => $this->challenge_length_override,
            'social_links' => $this->social_links,
        ]);
    }
}
