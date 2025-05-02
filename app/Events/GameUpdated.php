<?php

namespace App\Events;

use Carbon\Carbon;
use App\Models\Game;
use Thunk\Verbs\Event;
use App\States\GameState;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasUser;
use App\States\GameTemplateState;
use App\Events\Traits\HasGameTemplate;

class GameUpdated extends Event
{
    use HasGame, HasUser, HasGameTemplate;

    public Carbon $starts_at;

    public Carbon $ends_at;

    public bool $is_public;

    public bool $requires_admin_approval_to_join;

    public function apply(GameState $game)
    {
        $game->game_template_id = $this->game_template_id;
        $game->starts_at = $this->starts_at;
        $game->is_public = $this->is_public;
        $game->requires_admin_approval_to_join = $this->requires_admin_approval_to_join;
        $game->ends_at = $this->starts_at->copy()->addMinutes(
            $this->state(GameTemplateState::class)->durationOfAllChallengesInMinutes()
        );
        $game->players_can_join_late = $this->state(GameTemplateState::class)->players_can_join_late;
    }

    public function handle()
    {
        $game = Game::find($this->game_id)->update([
            'game_template_id' => $this->game_template_id,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'is_public' => $this->is_public,
            'requires_admin_approval_to_join' => $this->requires_admin_approval_to_join,
            'players_can_join_late' => $this->state(GameTemplateState::class)->players_can_join_late,
        ]);
    }
}
