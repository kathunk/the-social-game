<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasUser;
use App\Models\GameApplication;
use App\Models\Player;
use App\Models\User;
use App\States\GameApplicationState;
use App\States\GameState;
use App\States\PlayerState;
use App\States\UserState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class UserAdmittedToGame extends Event
{
    use HasGame, HasUser;

    #[StateId(PlayerState::class)]
    public ?int $player_id = null;

    #[StateId(GameApplicationState::class)]
    public int $application_id;

    public int $admin_id;

    public function validate()
    {
        if ($this->state(GameState::class)->requires_admin_approval_to_join) {
            $this->assert(
                $this->state(GameState::class)->admin_ids->contains($this->admin_id),
                'Admin is not an admin of this game',
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

        $this->assert(
            ! $this->state(GameState::class)->user_ids->contains($this->user_id),
            'User is already a player of this game',
        );
    }

    public function applyToGame(GameState $game)
    {
        $game->user_ids->push($this->user_id);
        $game->player_ids->push($this->player_id);
        $game->unaccepted_user_ids = $game->unaccepted_user_ids->reject(fn ($user_id) => $user_id === $this->user_id);
    }

    public function applyToUser(UserState $user)
    {
        $user->current_game_id = $this->game_id;
        $user->current_player_id = $this->player_id;
    }

    public function applyToPlayer(PlayerState $player)
    {
        $player->user_id = $this->user_id;
        $player->game_id = $this->game_id;
        $player->status = 'active';
        $player->name = $this->state(UserState::class)->name;
    }

    public function applyToApplication(GameApplicationState $application)
    {
        $application->status = 'accepted';
        $application->decided_by_id = $this->admin_id;
        $application->decided_at = now();
        $application->player_id = $this->player_id;
    }

    public function handle()
    {
        Player::create([
            'id' => $this->player_id,
            'user_id' => $this->user_id,
            'game_id' => $this->game_id,
            'name' => $this->state(UserState::class)->name,
            'status' => 'active',
        ]);

        $user = User::find($this->user_id);
        $user->current_game_id = $this->game_id;
        $user->current_player_id = $this->player_id;
        $user->save();

        GameApplication::find($this->application_id)->update([
            'status' => 'accepted',
            'decided_by_id' => $this->admin_id,
            'decided_at' => now(),
        ]);
    }
}
