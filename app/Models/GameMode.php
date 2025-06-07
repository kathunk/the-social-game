<?php

namespace App\Models;

use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Model;

class GameMode extends Model
{
    use HasSnowflakes;

    protected $guarded = [];

    protected $casts = [
        'is_archived' => 'boolean',
        'is_public' => 'boolean',
        'players_can_join_late' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope('not_archived', function ($builder) {
            $builder->where('is_archived', false);
        });
    }

    public function gameTemplates()
    {
        return $this->hasMany(GameTemplate::class);
    }
}
