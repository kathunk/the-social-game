<?php

namespace App\Models;

use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Model;

class GameTemplate extends Model
{
    use HasSnowflakes;

    protected $casts = [
        'team_names' => 'array',
        'challenges' => 'array',
    ];
}
