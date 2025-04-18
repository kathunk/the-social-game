<?php

namespace App\Models;

use App\Events\ChallengeStarted;
use App\Events\GameEnded;
use App\Events\GameStarted;
use App\States\GameState;
use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasSnowflakes;

    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function state(): GameState
    {
        return GameState::load($this->id);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function admins()
    {
        return $this->belongsToMany(User::class, 'game_admins');
    }

    public function applications()
    {
        return $this->hasMany(GameApplication::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function challenges()
    {
        return $this->hasMany(Challenge::class);
    }

    public function currentChallenge()
    {
        return $this->belongsTo(Challenge::class, 'current_challenge_id');
    }

    public function start()
    {
        GameStarted::fire(game_id: $this->id);

        $challenge = $this->challenges->sortBy('starts_at')->first();

        ChallengeStarted::fire(challenge_id: $challenge->id, game_id: $this->id);
    }

    public function end()
    {
        GameEnded::fire(game_id: $this->id);
    }
}
