<?php

namespace App\GameTemplates;

use App\Events\ChallengeCreated;
use App\Events\GameCreated;
use App\Events\TeamCreated;
use App\Models\Game;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Thunk\Verbs\Facades\Verbs;

class GameTemplate
{
    const GAME_NAME = 'name';

    const TEAM_NAMES = [
        'foo',
        'bar',
    ];

    public Carbon $ends_at;

    public function __construct(
        public ?Carbon $starts_at = null,
        public ?Collection $challenges = null,
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
        $challenges = $this->challenges ?? $this->challenges();

        foreach ($challenges as $challenge) {
            ChallengeCreated::fire(
                game_id: $this->game->id,
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
