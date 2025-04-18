<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\Models\Challenge;
use App\States\GameState;
use App\States\ChallengeState;
use Illuminate\Support\Carbon;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

class ChallengeCreated extends Event
{
    #[StateId(ChallengeState::class)]
    public ?int $challenge_id = null;

    #[StateId(GameState::class)]
    public int $game_id;

    public string $class_key;

    public Carbon $starts_at;

    public Carbon $ends_at;

    public function applyToChallenge(ChallengeState $challenge)
    {
        $challenge->game_id = $this->game_id;
        $challenge->class_key = $this->class_key;
        $challenge->starts_at = $this->starts_at;
        $challenge->ends_at = $this->ends_at;
        $challenge->status = 'upcoming';
    }

    public function applyToGame(GameState $game)
    {
        $game->challenge_ids->push($this->challenge_id);
    }

    public function handle()
    {
        Challenge::create([
            'id' => $this->challenge_id,
            'game_id' => $this->game_id,
            'class_key' => $this->class_key,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'status' => 'upcoming',
        ]);
    }
}
