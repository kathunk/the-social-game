<?php

namespace App\GameTemplates;

use App\Models\Game;
use App\Events\GameCreated;
use App\Events\TeamCreated;
use Illuminate\Support\Carbon;
use Thunk\Verbs\Facades\Verbs;
use App\Events\ChallengeCreated;

class GameTemplate
{
    CONST GAME_NAME = 'name';

    CONST TEAM_NAMES = [
        'foo',
        'bar',
    ];

    public Carbon $ends_at;

    public function __construct(
        public ?Carbon $starts_at = null,
    ) {
        $this->starts_at = $starts_at ?? $this->startTime();
        $this->ends_at = $this->ends_at();
    }

    public Game $game;

    public function createGame()
    {
        $game_id = GameCreated::fire(
            name: static::GAME_NAME,
            template_class: static::class,
            starts_at: $this->starts_at,
            ends_at: $this->ends_at,
        )->game_id;

        Verbs::commit();

        $this->game = Game::find($game_id);

        $this->seedTeams();
        $this->seedChallenges();
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

    public function seedChallenges()
    {
        foreach ($this->challenges() as $challenge) {
            ChallengeCreated::fire(
                game_id: $this->game->id,
                name: $challenge['class']::NAME,
                description: $challenge['class']::DESCRIPTION,
                starts_at: $challenge['starts_at'],
                ends_at: $challenge['ends_at'],
                class_key: $challenge['class']::key(),
            );
        }
    }

    public function challenges()
    {
        return collect();
    }

    public function startTime()
    {
        return now();
    }

    public function ends_at()
    {
        return $this->starts_at->addHours(18);
    }
}
