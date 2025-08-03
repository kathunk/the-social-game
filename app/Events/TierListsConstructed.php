<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\ModifierState;
use App\Events\Traits\HasGame;
use App\States\ChallengeState;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasChallenge;

class TierListsConstructed extends Event
{
    use HasPlayer, HasGame, HasChallenge, HasModifier;

    public array $submissions;

    public function validate()
    {
        // @todo
    }

    public function applyToChallenge(ChallengeState $challenge) 
    {
        $challenge->challenge_data['has_submitted'][$this->player_id] = true;
    }

    public function applyToModifier(ModifierState $modifier)
    {

    }

    public function handle()
    {
        // I'm on a paper chase until my toes bleed - Lil Wayne
    }
}
