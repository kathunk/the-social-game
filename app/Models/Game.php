<?php

namespace App\Models;

use App\Models\Player;
use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasSnowflakes;

    protected $guarded = [];

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function admins()
    {
        return $this->belongsToMany(User::class, 'game_admins');
    }
}
