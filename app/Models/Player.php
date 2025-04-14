<?php

namespace App\Models;

use App\Events\PlayerResigned;
use Thunk\Verbs\Facades\Verbs;
use App\Events\PlayerJoinedTeam;
use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasSnowflakes;

    protected $guarded = [];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
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
}
