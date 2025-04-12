<?php

namespace App\Events;

use App\Models\User;
use App\Models\Player;
use Thunk\Verbs\Event;
use App\States\GameState;
use App\States\UserState;
use App\States\PlayerState;
use App\Models\GameApplication;
use App\States\GameApplicationState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

class UserRejectedFromGame extends Event
{
    #[StateId(UserState::class)]
    public int $user_id;

    #[StateId(GameState::class)]
    public int $game_id;

    public int $admin_id;

    #[StateId(GameApplicationState::class)]
    public int $application_id;

    public function validate()
    {
        $this->assert(
            $this->state(GameState::class)->admin_ids->contains($this->admin_id),
            'Admin is not an admin of this game',
        );

        $this->assert(
            ! $this->state(GameState::class)->user_ids->contains($this->user_id),
            'User is already a player of this game',
        );

        $this->assert(
            ! $this->state(GameState::class)->rejected_user_ids->contains($this->user_id),
            'User is already rejected from this game',
        );

        $this->assert(
            $this->state(GameApplicationState::class)->user_id === $this->user_id,
            'User does not match the application',
        );

        $this->assert(
            $this->state(GameApplicationState::class)->game_id === $this->game_id,
            'Game does not match the application',
        );

        $this->assert(
            $this->state(GameApplicationState::class)->status === 'pending',
            'Application has already been decided',
        );
    }

    public function applyToGame(GameState $game)
    {
        $game->rejected_user_ids->push($this->user_id);
        $game->unaccepted_user_ids = $game->unaccepted_user_ids->reject(fn ($user_id) => $user_id === $this->user_id);
    }

    public function applyToApplication(GameApplicationState $application)
    {
        $application->status = 'rejected';
        $application->decided_by_id = $this->admin_id;
        $application->decided_at = now();
    }

    public function handle()
    {
        GameApplication::find($this->application_id)->update([
            'status' => 'rejected',
            'decided_by_id' => $this->admin_id,
            'decided_at' => now(),
        ]);
    }
}
