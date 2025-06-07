<?php

namespace App\Models;

use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Model;

class GameTemplate extends Model
{
    use HasSnowflakes;

    protected $guarded = [];

    protected $casts = [
        'team_names' => 'array',
        'challenges' => 'array',
        'modifiers' => 'array',
        'is_archived' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope('not_archived', function ($builder) {
            $builder->where('is_archived', false);
        });
    }

    public function gameMode()
    {
        return $this->belongsTo(GameMode::class);
    }

    public function getTotalDurationAttribute(): int
    {
        return collect($this->challenges)->sum(fn ($c) => $c['duration'] ?? 0);
    }
}
