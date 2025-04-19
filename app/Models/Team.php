<?php

namespace App\Models;

use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use HasSnowflakes;

    protected $guarded = [];

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function historicalPlayers(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'historical_team_players', 'team_id', 'player_id')
            ->using(HistoricalTeamPlayer::class)
            ->where('historical_team_players.game_id', $this->game_id)
            ->withPivot(['player_id', 'team_id'])
            ->withTimestamps();
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function scoreHistoryEntries()
    {
        return $this->hasMany(ScoreHistoryEntry::class);
    }
}
