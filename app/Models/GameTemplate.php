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
        'players_can_join_late' => 'boolean',
        'is_archived' => 'boolean',
        'is_public' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope('not_archived', function ($builder) {
            $builder->where('is_archived', false);
        });
    }

    public function getTotalDurationAttribute(): int
    {
        return collect($this->challenges)->sum(fn ($c) => $c['duration'] ?? 0);
    }
}
