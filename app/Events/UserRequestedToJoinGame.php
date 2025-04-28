<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasUser;
use App\Models\GameApplication;
use App\Models\User;
use App\States\GameApplicationState;
use App\States\GameState;
use App\States\UserState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class UserRequestedToJoinGame extends Event
{
    use HasGame, HasUser;

    #[StateId(GameApplicationState::class)]
    public ?int $application_id = null;

    public function validate()
    {
        $this->assert(
            ! $this->state(GameState::class)->user_ids->contains($this->user_id),
            'User is already a player of this game',
        );

        $this->assert(
            ! $this->state(GameState::class)->rejected_user_ids->contains($this->user_id),
            'User is already rejected from this game',
        );

        $this->assert(
            ! $this->state(GameState::class)->unaccepted_user_ids->contains($this->user_id),
            'User already applied to this game',
        );
    }

    public function applyToGame(GameState $game)
    {
        $game->unaccepted_user_ids->push($this->user_id);
    }

    public function applyToApplication(GameApplicationState $application)
    {
        $application->user_id = $this->user_id;
        $application->game_id = $this->game_id;
        $application->status = 'pending';
    }

    public function applyToUser(UserState $user)
    {
        $user->application_ids->push($this->application_id);
        $user->current_game_id = $this->game_id;
    }

    public function handle()
    {
        $user = User::find($this->user_id);
        $user->current_game_id = $this->game_id;
        $user->save();

        GameApplication::create([
            'id' => $this->application_id,
            'user_id' => $this->user_id,
            'game_id' => $this->game_id,
            'status' => 'pending',
        ]);
    }
}
