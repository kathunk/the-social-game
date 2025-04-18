<?php

namespace App\Models;

use App\Events\ChallengeEnded;
use App\States\ChallengeState;
use App\Events\ChallengeStarted;
use App\Challenges\ChallengeRegistry;
use Illuminate\Database\Eloquent\Model;
use App\Challenges\Classes\BaseChallengeClass;

class Challenge extends Model
{
    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
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
}
