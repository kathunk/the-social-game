<?php

namespace App\States;

use Thunk\Verbs\State;
use Illuminate\Support\Carbon;
use App\States\GameTemplateState;
use Illuminate\Support\Collection;

class GameState extends State
{
    public string $name;

    public string $template_class;

    public string $status;

    public Collection $user_ids;

    public Collection $unaccepted_user_ids;

    public Collection $rejected_user_ids;

    public Collection $player_ids;

    public Collection $resigned_player_ids;

    public Collection $removed_player_ids;

    public Collection $admin_ids;

    public Collection $team_ids;

    public Carbon $starts_at;

    public Carbon $ends_at;

    public Collection $challenge_ids;

    public $current_challenge_id;

    public int $game_template_id;

    public bool $is_public;

    public bool $requires_admin_approval_to_join;

    public int $code;

    public function __construct()
    {
        $this->player_ids = collect();
        $this->admin_ids = collect();
        $this->user_ids = collect();
        $this->rejected_user_ids = collect();
        $this->unaccepted_user_ids = collect();
        $this->team_ids = collect();
        $this->resigned_player_ids = collect();
        $this->challenge_ids = collect();
        $this->removed_player_ids = collect();
    }

    public function players()
    {
        return $this->player_ids->map(fn (int $player_id) => PlayerState::load($player_id));
    }

    public function admins()
    {
        return $this->admin_ids->map(fn (int $admin_id) => UserState::load($admin_id));
    }

    public function users()
    {
        return $this->user_ids->map(fn (int $user_id) => UserState::load($user_id));
    }

    public function teams()
    {
        return $this->team_ids->map(fn (int $team_id) => TeamState::load($team_id));
    }

    public function challenges()
    {
        return $this->challenge_ids->map(fn (int $challenge_id) => ChallengeState::load($challenge_id));
    }

    public function currentChallenge()
    {
        return ChallengeState::load($this->current_challenge_id);
    }

    public function template(): GameTemplateState
    {
        return GameTemplateState::load($this->game_template_id);
    }
}
