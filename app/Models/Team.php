<?php

namespace App\Models;

use App\States\TeamState;
use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasSnowflakes;

    protected $guarded = [];

    public function state()
    {
        return TeamState::load($this->id);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
