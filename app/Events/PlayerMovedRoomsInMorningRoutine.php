<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\GameState;
use App\States\PlayerState;
use App\Events\Traits\HasGame;
use App\States\ChallengeState;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasChallenge;

class PlayerMovedRoomsInMorningRoutine extends Event
{
    use HasGame, HasPlayer, HasChallenge;

    public string $room;

    public function validate(GameState $game, PlayerState $player, ChallengeState $challenge)
    {
        $this->assert(
            $challenge->challenge_data['rooms'][$this->room] === null,
            'Room is no longer empty',
        );

        $this->assert(
            ! in_array($player->id, $challenge->challenge_data['has_moved']),
            'Player has already moved',
        );

        $this->assert(
            in_array($this->room, array_keys($challenge->challenge_data['rooms'])),
            'Room is not valid',
        );
    }

    public function applyToChallenge(ChallengeState $challenge)
    {
        $challenge->challenge_data['rooms'][$this->room] = $this->player_id;
        $challenge->challenge_data['has_moved'][] = $this->player_id;
    }

    public function handle()
    {
        $this->challenge()->updateModelWithStateData();
    }
}
