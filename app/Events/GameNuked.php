<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Models\Game;
use App\States\GameState;
use Thunk\Verbs\Event;

/**
 * Completely removes a game and everything attached to it: players,
 * teams, challenges, modifiers, configurations, applications, and admin
 * assignments. Unlike GameCanceled (upcoming games only, keeps the row),
 * this works on games in any status and deletes the models outright.
 *
 * The event itself stays in the event store as a compensating event:
 * a replay re-runs the deletion, so rebuilt state stays consistent.
 */
class GameNuked extends Event
{
    use HasGame;

    public int $admin_id;

    public function applyToGame(GameState $game)
    {
        $game->status = 'nuked';
    }

    public function handle()
    {
        $game = Game::find($this->game_id);

        // Already gone (e.g. replay after the original deletion) - nothing to do
        if (! $game) {
            return;
        }

        // Detach users from the game before their player rows disappear
        $game->players->each(function ($player) {
            $player->user?->update([
                'current_game_id' => null,
                'current_player_id' => null,
            ]);
        });

        // Deletion order matters: no FK cascades on these tables.
        // players.team_id references teams, so players go first.
        $game->update(['current_challenge_id' => null]);
        $game->players()->delete();
        $game->teams()->delete();
        $game->challenges()->delete();
        $game->modifiers()->delete();
        $game->modifierConfigurations()->delete();
        $game->applications()->delete();
        $game->admins()->detach();
        $game->delete();
    }
}
