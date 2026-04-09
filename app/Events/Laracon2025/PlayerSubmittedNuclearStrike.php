<?php

namespace App\Events\Laracon2025;

use App\Events\Traits\HasActiveGame;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasPlayerOnTeam;
use App\Models\Challenge;
use App\States\ChallengeState;
use App\States\PlayerState;
use Thunk\Verbs\Event;

class PlayerSubmittedNuclearStrike extends Event
{
    use HasActiveGame, HasChallenge, HasPlayerOnTeam;

    public string $strike_type;

    public string $target_code;

    public function validate(PlayerState $player, ChallengeState $challenge)
    {
        $team_id = $player->team_id;
        $team_data = $challenge->challenge_data[$team_id];
        $ally_team_id = $team_data['ally_team_id'];
        $ally_team_code = $challenge->challenge_data[$ally_team_id]['code'];

        $this->assert(
            $player->team_id === $this->team_id,
            'Player is not on team'
        );

        $this->assert(
            ! $team_data['has_launched'],
            'Your team has already launched a nuclear strike'
        );

        $this->assert(
            $this->target_code === $ally_team_code,
            'Invalid nuclear code'
        );

        $this->assert(
            in_array($this->strike_type, ['carpet_bomb', 'nuke_ally']),
            'Invalid strike type'
        );
    }

    public function applyToChallenge(ChallengeState $challenge)
    {
        $team_id = $this->team_id;
        $challenge->challenge_data[$team_id]['has_launched'] = true;
        $challenge->challenge_data[$team_id]['strike_type'] = $this->strike_type;
    }

    public function handle()
    {
        $challenge = Challenge::find($this->challenge_id);
        $challenge->challenge_data = $this->state(ChallengeState::class)->challenge_data;
        $challenge->save();
    }
}
