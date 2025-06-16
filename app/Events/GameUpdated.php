<?php

namespace App\Events;

use App\Models\Game;
use Thunk\Verbs\Event;
use App\States\GameState;
use App\States\UserState;
use App\States\GameModeState;
use App\Events\Traits\HasGame;
use Illuminate\Support\Carbon;
use App\States\GameTemplateState;
use App\Events\Traits\HasGameMode;
use App\Events\Traits\HasGameTemplate;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

class GameUpdated extends Event
{
    use HasGame, HasGameMode, HasGameTemplate;

    #[StateId(UserState::class)]
    public ?int $user_id = null;

    public Carbon $starts_at;

    public Carbon $ends_at;

    public bool $is_public;

    public bool $requires_admin_approval_to_join;

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
        $game->starts_at = Carbon::parse($this->starts_at);
        $game->is_public = $this->is_public;
        $game->requires_admin_approval_to_join = $this->requires_admin_approval_to_join;
        $game->ends_at = Carbon::parse($this->starts_at)->addMinutes(
            $this->state(GameTemplateState::class)->durationOfAllChallengesInMinutes()
        );
        $game->players_can_join_late = $this->state(GameTemplateState::class)->players_can_join_late;
    }

    public function handle()
    {
        Game::find($this->game_id)->update([
            'game_template_id' => $this->game_template_id,
            'game_mode_id' => $this->game_mode_id,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'is_public' => $this->is_public,
            'requires_admin_approval_to_join' => $this->requires_admin_approval_to_join,
            'players_can_join_late' => $this->state(GameTemplateState::class)->players_can_join_late,
        ]);
    }
}
