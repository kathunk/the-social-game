<?php

namespace App\Models;

use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasSnowflakes;

    protected $guarded = [];

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function scoreHistoryEntries()
    {
        return $this->hasMany(ScoreHistoryEntry::class);
    }
}
