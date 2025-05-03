<?php

namespace App\Models;

use App\Modifiers\Classes\BaseModifierClass;
use App\Modifiers\ModifierRegistry;
use App\States\ModifierState;
use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Model;

class Modifier extends Model
{
    use HasSnowflakes;

    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'modifier_data' => 'array',
    ];

    public function state(): ModifierState
    {
        return ModifierState::load($this->id);
    }

    public function handler(): BaseModifierClass
    {
        return ModifierRegistry::retrieveFromModel($this->class_key, $this);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function updateModelWithStateData()
    {
        $this->update(['modifier_data' => $this->state()->modifier_data]);
    }
}
