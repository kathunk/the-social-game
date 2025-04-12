<?php

namespace App\Models;

use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Model;

class GameApplication extends Model
{
    use HasSnowflakes;

    protected $guarded = [];
    
    protected $casts = [
        'decided_at' => 'datetime',
    ];
    
    public function game()
    {
        return $this->belongsTo(Game::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function decidedBy()
    {
        return $this->belongsTo(User::class, 'decided_by_id');
    }
}
