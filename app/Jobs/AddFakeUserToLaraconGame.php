<?php

namespace App\Jobs;

use App\Models\Game;
use App\Models\Team;
use App\Models\User;
use App\Models\Modifier;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class AddFakeUserToLaraconGame implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Team $team,
        public User $admin,
        public Game $game,
        public Modifier $secret_alliance_modifier,
    )
    {
        //
    }

    public function handle(): void
    {
        $user = User::fromTemplate(
            name: fake()->name(),
            email: fake()->email(),
            encrypted_password: bcrypt('password'),
        );

        $user->requestToJoinGame($this->game);
        $user->admitToGame($this->game, $this->admin);
        $player = $user->fresh()->currentPlayer;

        $player->joinTeam($this->team);
        $this->secret_alliance_modifier->handler()->onSecretDiscovered($player);
    }
}
