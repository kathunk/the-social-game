<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoreHistoryEntry extends Model
{
    protected $guarded = [];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
