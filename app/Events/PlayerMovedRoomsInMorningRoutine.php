<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\GameState;
use App\States\PlayerState;
use App\States\ModifierState;
use App\Events\Traits\HasGame;
use App\States\ChallengeState;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasChallenge;
use App\Modifiers\Classes\MorningRoutineProgress;

class PlayerMovedRoomsInMorningRoutine extends Event
{
    use HasGame, HasPlayer, HasChallenge, HasModifier;

    public string $room;

    public function validate(GameState $game, PlayerState $player, ChallengeState $challenge, ModifierState $modifier)
    {
        $this->assert(
            $modifier->class_key === MorningRoutineProgress::key(),
            'Modifier must be MorningRoutineProgress',
        );

        $this->assert(
            $modifier->modifier_data['rooms'][$this->room] === null,
            'Room is no longer empty',
        );

        $this->assert(
            ! in_array($player->id, $challenge->challenge_data['has_moved']),
            'Player has already moved',
        );

        $this->assert(
            in_array($this->room, array_keys($modifier->modifier_data['rooms'])),
            'Room is not valid',
        );
    }

    public function applyToChallenge(ChallengeState $challenge)
    {
        $challenge->challenge_data['has_moved'][] = $this->player_id;
    }

    public function applyToModifier(ModifierState $modifier)
    {
        $modifier->modifier_data['rooms'][$this->room] = $this->player_id;
    }

    public function handle()
    {
        $this->challenge()->updateModelWithStateData();
    }
}
