<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasUser;
use App\States\GameState;
use App\States\PlayerState;
use Thunk\Verbs\Event;

class PlayerAbandonedGame extends Event
{
    use HasGame, HasPlayer, HasUser;

    public function applyToPlayer(PlayerState $player)
    {
        $player->status = 'inactive';
    }

    public function applyToGame(GameState $game)
    {
        $game->user_ids = $game->user_ids->reject(
            fn ($user_id) => $user_id === $this->user_id
        );
        $game->player_ids = $game->player_ids->reject(
            fn ($player_id) => $player_id === $this->player_id
        );
    }

    public function handle()
    {
        $this->user()->update([
            'current_game_id' => null,
            'current_player_id' => null,
        ]);

        $player = $this->player();

        $player->status = 'inactive';
        $player->team_id = null;
        $player->delete();
    }
}
