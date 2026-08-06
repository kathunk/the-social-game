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
        'skips_pre_game_lobby' => 'boolean',
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

    public function selectTemplateForUser(User $user)
    {
        $available_templates = $user->is_super_admin
            ? $this->gameTemplates
            : $this->gameTemplates->where('is_public', true);

        $played_template_ids = $user->games->pluck('template_id');

        $available_templates = $available_templates
            ->shuffle()
            ->sortBy(fn ($t) => $played_template_ids->filter(fn ($id) => $id === $t->id)->count());

        return $available_templates->first();
    }
}
