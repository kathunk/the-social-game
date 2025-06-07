<?php

namespace App\Events;

use App\Events\Traits\HasGameMode;
use App\Events\Traits\HasGameTemplate;
use App\Events\Traits\HasUser;
use App\Models\Game;
use App\States\GameModeState;
use App\States\GameState;
use App\States\GameTemplateState;
use Carbon\Carbon;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class GameCreated extends Event
{
    use HasGameMode, HasGameTemplate, HasUser;

    #[StateId(GameState::class)]
    public ?int $game_id = null;

    public string $name;

    public Carbon $starts_at;

    public Carbon $ends_at;

    public ?bool $is_public = false;

    public bool $requires_admin_approval_to_join;

    public int $code;

    public function apply(GameState $game)
    {
        $game->name = $this->name;
        $game->status = 'upcoming';
        $game->game_template_id = $this->game_template_id;
        $game->game_mode_id = $this->game_mode_id;
        $game->starts_at = $this->starts_at;
        $game->is_public = $this->is_public;
        $game->requires_admin_approval_to_join = $this->requires_admin_approval_to_join;
        $game->code = $this->code;
        $game->ends_at = $this->starts_at->copy()->addMinutes(
            $this->state(GameTemplateState::class)->durationOfAllChallengesInMinutes()
        );
        $game->players_can_join_late = $this->state(GameModeState::class)->players_can_join_late;
    }

    public function handle()
    {
        Game::create([
            'id' => $this->game_id,
            'name' => $this->name,
            'status' => 'upcoming',
            'game_template_id' => $this->game_template_id,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'is_public' => $this->is_public,
            'requires_admin_approval_to_join' => $this->requires_admin_approval_to_join,
            'code' => $this->code,
            'players_can_join_late' => $this->state(GameModeState::class)->players_can_join_late,
        ]);
    }
}
