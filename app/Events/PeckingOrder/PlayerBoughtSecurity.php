<?php

namespace App\Events\PeckingOrder;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\States\ChallengeState;
use App\States\PlayerState;
use Thunk\Verbs\Event;

class PlayerBoughtSecurity extends Event
{
    use HasActivePlayer, HasChallenge, HasGame;

    public int $cost_in_hidden_points;

    public int $cost_in_points;

    public int $downvotes_to_ignore;

    public int $upvotes_to_ignore;

    public function applyToChallenge(ChallengeState $challenge)
    {
        $challenge->challenge_data['secure_player_ids'][] = $this->player_id;
    }

    public function applyToPlayer(PlayerState $player)
    {
        if ($this->cost_in_hidden_points > 0) {
            $player->addToScoreHistory(
                icon: '🛡️',
                points: -$this->cost_in_hidden_points,
                description: 'Bought immunity',
                is_hidden: true,
            );
        }

        if ($this->cost_in_points > 0) {
            $player->addToScoreHistory(
                icon: '🛡️',
                points: -$this->cost_in_points,
                description: 'Bought immunity',
            );
        }
    }

    public function handle()
    {
        $this->challenge()->updateModelWithStateData();

        $this->applyToChallenge($this->challenge()->state());
        $this->game()->players->each(fn ($p) => $p->updateModelWithStateData());
    }
}
