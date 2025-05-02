<?php

namespace App\Models;

use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    use HasSnowflakes;

    protected $casts = [
        'ends_at' => 'datetime',
    ];

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->ends_at === null || $this->ends_at->isFuture();
    }
}
