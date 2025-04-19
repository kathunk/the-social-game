<?php

namespace App\Models;

use App\States\PlayerState;
use App\Events\PlayerResigned;
use Thunk\Verbs\Facades\Verbs;
use App\Events\PlayerJoinedTeam;
use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Player extends Model
{
    use HasSnowflakes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'previous_team_ids' => Collection::class,
        ];
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function historicalTeams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'historical_team_players', 'player_id', 'team_id')
            ->using(HistoricalTeamPlayer::class)
            ->where('historical_team_players.game_id', $this->game_id)
            ->withPivot(['player_id', 'team_id'])
            ->withTimestamps();
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function canSwitchTeams(): bool
    {
        // @todo we will have game logic turn this on and off
        return true;
    }

    public function joinTeam(Team $team)
    {
        PlayerJoinedTeam::fire(
            player_id: $this->id,
            team_id: $team->id,
            game_id: $this->game_id,
            previous_team_id: $this->team_id,
        );

        Verbs::commit();

        return $this->fresh();
    }

    public function resign(int $points)
    {
        PlayerResigned::fire(
            player_id: $this->id,
            points: $points,
            game_id: $this->game_id,
            team_id: $this->team_id,
        );

        Verbs::commit();

        return $this->fresh();
    }

    public function state(): PlayerState
    {
        return PlayerState::load($this->id);
    }
}
