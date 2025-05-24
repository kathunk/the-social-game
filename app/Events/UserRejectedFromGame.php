<?php

namespace App\Events;

use App\Events\Traits\HasActiveGame;
use App\Events\Traits\HasGameApplication;
use App\Events\Traits\HasUser;
use App\Models\GameApplication;
use App\States\GameApplicationState;
use App\States\GameState;
use Thunk\Verbs\Event;

class UserRejectedFromGame extends Event
{
    use HasActiveGame, HasGameApplication, HasUser;

    public int $admin_id;

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

        $this->user()->update([
            'current_game_id' => null,
            'current_player_id' => null,
        ]);
    }
}
