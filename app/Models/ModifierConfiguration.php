<?php

namespace App\Models;

use App\States\ModifierConfigurationState;
use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModifierConfiguration extends Model
{
    /** @use HasFactory<\Database\Factories\ModifierConfigurationFactory> */
    use HasFactory, HasSnowflakes;

    protected $guarded = [];

    protected $casts = [
        'modifier_data' => 'array',
    ];

    public function state(): ModifierConfigurationState
    {
        return ModifierConfigurationState::load($this->id);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}
