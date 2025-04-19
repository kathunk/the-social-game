<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class HistoricalTeamPlayer extends Pivot
{
    protected $table = 'historical_team_players';

    protected $fillable = [
        'player_id',
        'team_id',
        'game_id',
        'joined_at',
    ];
}
