<?php

namespace App\Models;

use App\Events\PlayerJoinedTeam;
use App\States\PlayerState;
use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Model;
use Thunk\Verbs\Facades\Verbs;

class Player extends Model
{
    use HasSnowflakes;

    protected $guarded = [];

    public function state(): PlayerState
    {
        return PlayerState::load($this->id);
    }

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

    public function updateModelWithStateData()
    {
        $this->update([
            'score' => $this->state()->score(),
            'hidden_score' => $this->state()->score(include_hidden: true),
        ]);
    }
}
