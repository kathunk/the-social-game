<?php

namespace App\Models;

use App\Challenges\ChallengeRegistry;
use App\Challenges\Classes\BaseChallengeClass;
use App\Events\ChallengeEnded;
use App\Events\ChallengeStarted;
use App\States\ChallengeState;
use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'challenge_data' => 'array',
    ];

    public function state(): ChallengeState
    {
        return ChallengeState::load($this->id);
    }

    public function handler(): BaseChallengeClass
    {
        return ChallengeRegistry::retrieveFromModel($this->class_key, $this);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function start()
    {
        ChallengeStarted::fire(
            challenge_id: $this->id,
            game_id: $this->game_id
        );
    }

    public function end()
    {
        ChallengeEnded::fire(
            challenge_id: $this->id,
            game_id: $this->game_id
        );
    }

    public function updateModelWithStateData()
    {
        $this->update(['challenge_data' => $this->state()->challenge_data]);
    }
}
