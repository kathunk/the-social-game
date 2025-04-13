<?php

namespace App\GameTemplates;

use App\Models\Game;
use App\Events\GameCreated;
use App\Events\TeamCreated;
use Thunk\Verbs\Facades\Verbs;

class GameTemplate
{
    CONST GAME_NAME = 'name';

    CONST TEAM_NAMES = [
        'foo',
        'bar',
    ];

    public Game $game;

    public function createGame()
    {
        $game_id = GameCreated::fire([
            'name' => static::GAME_NAME,
            'template_class' => static::class,
        ])->game_id;

        Verbs::commit();

        $this->game = Game::find($game_id);

        $this->seedTeams();

        return $this->game;
    }

    public function seedTeams()
    {
        foreach (static::TEAM_NAMES as $teamName) {
            TeamCreated::fire(
                game_id: $this->game->id,
                name: $teamName,
            );
        }
    }
}
